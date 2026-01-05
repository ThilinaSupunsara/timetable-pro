import sys
import json
from collections import defaultdict

try:
    from ortools.sat.python import cp_model
except ImportError:
    print(json.dumps({"status": "error", "message": "OR-Tools not found. Run: pip install ortools"}))
    sys.exit(1)

def diagnose_conflicts(allocations, blocked_set, break_map, num_days, num_periods):
    """ 1. Basic Diagnosis """
    for alloc in allocations:
        if alloc.get('is_fixed'):
            s_id = str(alloc['section_id'])
            sec_name = alloc.get('section_name', 'Unknown')
            t_name = alloc.get('teacher_name', 'Teacher')
            s_name = alloc.get('subject_name', 'Subject')
            f_day = alloc['fixed_day']
            f_period = alloc['fixed_period']
            duration = int(alloc.get('duration', 1))

            if (f_period - 1) + duration > num_periods:
                return f"❌ [{sec_name}] Range Error: '{s_name}' ends after period {num_periods}."

            my_breaks = break_map.get(s_id, [])
            my_breaks_0 = [p - 1 for p in my_breaks]
            for k in range(duration):
                current_p = (f_period - 1) + k
                if current_p in my_breaks_0:
                    return f"❌ [{sec_name}] Interval Conflict: '{s_name}' is Fixed at Period {current_p + 1}, which is a Break."

            if alloc.get('teacher_id'):
                t_id = alloc['teacher_id']
                for k in range(duration):
                    if (t_id, f_day - 1, (f_period - 1) + k) in blocked_set:
                        return f"❌ [{sec_name}] Teacher Clash: '{t_name}' is Fixed here but busy in another class."
    return None

def check_fragmentation(allocations, blocked_set, break_map, num_days, num_periods):
    """ 2. Double Period Fitting Check """
    for alloc in allocations:
        duration = int(alloc.get('duration', 1))
        if duration > 1:
            t_id = alloc.get('teacher_id')
            s_id = str(alloc['section_id'])
            t_name = alloc.get('teacher_name', 'Teacher')
            s_name = alloc.get('subject_name', 'Subject')
            sec_name = alloc.get('section_name', 'Class')

            my_breaks = set([p - 1 for p in break_map.get(s_id, [])])
            valid_start_slots = 0
            for d in range(num_days):
                for p in range(num_periods - duration + 1):
                    can_fit_here = True
                    for k in range(duration):
                        current_p = p + k
                        if current_p in my_breaks:
                            can_fit_here = False; break
                        if t_id and (t_id, d, current_p) in blocked_set:
                            can_fit_here = False; break
                    if can_fit_here: valid_start_slots += 1

            if valid_start_slots == 0:
                return f"❌ [{sec_name}] Space Error: No space found for '{s_name}' ({duration} periods). {t_name} is busy or breaks block it."
    return None

def solve_timetable(data):
    try:
        allocations = data.get('allocations', [])
        blocked = data.get('blocked', [])
        break_map = data.get('break_map', {})
        num_periods = int(data.get('num_periods', 9))
        num_days = 5

        blocked_set = set()
        for b in blocked:
            blocked_set.add((b['teacher_id'], b['day'] - 1, b['period'] - 1))

        # --- DIAGNOSTICS ---
        err1 = diagnose_conflicts(allocations, blocked_set, break_map, num_days, num_periods)
        if err1: return json.dumps({"status": "error", "message": err1})

        err2 = check_fragmentation(allocations, blocked_set, break_map, num_days, num_periods)
        if err2: return json.dumps({"status": "error", "message": err2})

        # --- MODELING ---
        model = cp_model.CpModel()
        schedule = {}

        # Create variables
        for alloc in allocations:
            duration = int(alloc.get('duration', 1))
            for d in range(num_days):
                for p in range(num_periods - duration + 1):
                    schedule[(alloc['id'], d, p)] = model.NewBoolVar(f"alloc_{alloc['id']}_d{d}_p{p}")

        # ==========================================
        # 0. BUCKET SYNCHRONIZATION (NEW LOGIC)
        # එකම Bucket එකේ විෂයන් එකම වෙලාවට වැටිය යුතුයි.
        # ==========================================
        bucket_groups = defaultdict(list)
        for alloc in allocations:
            b_name = alloc.get('bucket_name')
            if b_name:
                # Group by (Section ID, Bucket Name)
                key = (alloc['section_id'], b_name)
                bucket_groups[key].append(alloc)

        # Map to identify the "Leader" of each bucket
        # (Follower ID -> Leader ID)
        bucket_leader_map = {}

        for key, group in bucket_groups.items():
            leader = group[0] # First one is the leader
            duration = int(leader.get('duration', 1))

            for follower in group[1:]:
                bucket_leader_map[follower['id']] = leader['id']
                # Leader සහ Follower එකම වෙලාවට වැඩ කළ යුතුයි
                for d in range(num_days):
                    for p in range(num_periods - duration + 1):
                        model.Add(schedule[(leader['id'], d, p)] == schedule[(follower['id'], d, p)])

        # ==========================================

        # 1. Breaks
        for alloc in allocations:
            s_id = str(alloc['section_id'])
            duration = int(alloc.get('duration', 1))
            my_breaks = set([x - 1 for x in break_map.get(s_id, [])])
            if my_breaks:
                for d in range(num_days):
                    for p in range(num_periods - duration + 1):
                        touches = False
                        for k in range(duration):
                            if (p + k) in my_breaks:
                                touches = True; break
                        if touches:
                            model.Add(schedule[(alloc['id'], d, p)] == 0)

        # 2. Blocked Slots (Teachers)
        for alloc in allocations:
            t_id = alloc['teacher_id']
            duration = int(alloc.get('duration', 1))
            if t_id:
                for d in range(num_days):
                    for p in range(num_periods - duration + 1):
                        conflict = False
                        for k in range(duration):
                            if (t_id, d, p + k) in blocked_set:
                                conflict = True; break
                        if conflict:
                            model.Add(schedule[(alloc['id'], d, p)] == 0)

        # 3. Fixed Slots
        for alloc in allocations:
            if alloc.get('is_fixed'):
                f_day = alloc['fixed_day'] - 1
                f_period = alloc['fixed_period'] - 1
                duration = int(alloc.get('duration', 1))
                if 0 <= f_day < num_days and 0 <= f_period <= (num_periods - duration):
                    model.Add(schedule[(alloc['id'], f_day, f_period)] == 1)

        # 4. Total Count
        for alloc in allocations:
            duration = int(alloc.get('duration', 1))
            needed = int(alloc['total_periods']) // duration
            model.Add(sum(schedule[(alloc['id'], d, p)]
                          for d in range(num_days)
                          for p in range(num_periods - duration + 1)) == needed)

        # 5. Class Conflict (UPDATED FOR BUCKETS)
        # එකම Bucket එකේ අයව Conflict එකක් විදියට ගණන් ගන්න හොඳ නෑ.
        # ඒ නිසා අපි ගණන් ගන්නේ "Leader" සහ "Non-Bucket Items" විතරයි.
        allocs_per_section = defaultdict(list)
        for alloc in allocations:
            # If it's a follower in a bucket, skip it for class conflict check
            if alloc['id'] in bucket_leader_map:
                continue
            allocs_per_section[alloc['section_id']].append(alloc)

        for s_id, items in allocs_per_section.items():
            for d in range(num_days):
                for p in range(num_periods):
                    active = []
                    for alloc in items:
                        duration = int(alloc.get('duration', 1))
                        start_range = range(max(0, p - duration + 1), min(p + 1, num_periods - duration + 1))
                        for start_p in start_range:
                            if (alloc['id'], d, start_p) in schedule:
                                active.append(schedule[(alloc['id'], d, start_p)])
                    if active: model.Add(sum(active) <= 1)

        # 6. Teacher Conflict
        allocs_per_teacher = defaultdict(list)
        for alloc in allocations:
            if alloc['teacher_id']: allocs_per_teacher[alloc['teacher_id']].append(alloc)

        for t_id, items in allocs_per_teacher.items():
            for d in range(num_days):
                for p in range(num_periods):
                    active = []
                    for alloc in items:
                        duration = int(alloc.get('duration', 1))
                        start_range = range(max(0, p - duration + 1), min(p + 1, num_periods - duration + 1))
                        for start_p in start_range:
                            if (alloc['id'], d, start_p) in schedule:
                                active.append(schedule[(alloc['id'], d, start_p)])
                    if active: model.Add(sum(active) <= 1)

        # 7. Daily Limit
        for alloc in allocations:
            total_p = int(alloc['total_periods'])
            max_daily = 2 if total_p > 5 else 1
            for d in range(num_days):
                duration = int(alloc.get('duration', 1))
                model.Add(sum(schedule[(alloc['id'], d, p)]
                              for p in range(num_periods - duration + 1)) <= max_daily)

        # Solve
        solver = cp_model.CpSolver()
        solver.parameters.max_time_in_seconds = 60.0
        status = solver.Solve(model)

        if status == cp_model.OPTIMAL or status == cp_model.FEASIBLE:
            final_schedule = []
            for alloc in allocations:
                duration = int(alloc.get('duration', 1))
                for d in range(num_days):
                    for p in range(num_periods - duration + 1):
                        if solver.Value(schedule[(alloc['id'], d, p)]) == 1:
                            for k in range(duration):
                                final_schedule.append({
                                    'allocation_id': alloc['id'],
                                    'day': d + 1,
                                    'period': p + k + 1
                                })
            return json.dumps({"status": "success", "data": final_schedule})
        else:
            class_name = "Unknown Class"
            if allocations:
                class_name = allocations[0].get('section_name', 'Unknown')
            return json.dumps({"status": "error", "message": f"[{class_name}] Complex Conflict: Unable to fit subjects. Check Bucket definitions and Teacher availability."})

    except Exception as e:
        return json.dumps({"status": "error", "message": str(e)})

if __name__ == "__main__":
    input_str = sys.stdin.read()
    if input_str:
        try:
            data = json.loads(input_str)
            print(solve_timetable(data))
        except:
            print(json.dumps({"status": "error", "message": "Invalid JSON input"}))
