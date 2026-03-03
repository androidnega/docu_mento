# Structure Alignment: Coordinator, School, Department, Academic Year, Users

This document confirms how the current schema and implementation support:

- Coordinator affiliated to a **school** and **department**
- Coordinator can upload **Students** (role = student) and **Supervisors** (role = supervisor)
- Both must belong to an **academic year**
- Academic year **visually groups students** (e.g. cards per year)
- **Supervisors** tied to academic year

---

## 1. Confirmed current structure

### Tables and relationships

| Entity | Table | Key relationships |
|--------|--------|-------------------|
| **School** | `schools` | Top-level; departments belong to school. |
| **Department** | `departments` | `school_id` → schools; unique(school_id, name). |
| **Academic year** | `academic_years` | `department_id` → departments; unique(department_id, year). |
| **Role** | `roles` | `department_id` → departments; unique(department_id, name). |
| **User** | `users` | `department_id` → departments, `role_id` → roles (or legacy `role` string), **`academic_year_id`** → academic_years. |
| **Supervisor** | `supervisors` | `user_id` → users (1:1). Supervisor “is” a user; academic year comes from `users.academic_year_id`. |
| **Deadline** | `deadlines` | **`academic_year_id`** → academic_years (added). |

So:

- **schools** → **departments** (belongs to school) → **academic_years** (belongs to department)
- **roles** (belongs to department) → **users** (belongs to role / department)
- **users** have **academic_year_id** and **department_id**
- **deadlines** have **academic_year_id**

That matches the intended backbone.

---

## 2. Coordinator affiliation (role → department)

- Coordinator is a **user** with coordinator role (`users.role` or `users.role_id` → **roles**).
- **Roles belong to department** (`roles.department_id`). So: **Coordinator → role → department → academic_years**.
- Access logic: **`$coordinator->roleModel->department_id`** (or fallback `$coordinator->department_id` for legacy). Use **`$coordinator->coordinatorDepartmentId()`** everywhere.
- Department has **`departments.school_id`** → coordinator is affiliated to **school** via role’s department.

Implementation:

- All coordinator scope uses **`$user->coordinatorDepartmentId()`** (which returns `role->department_id ?? user->department_id`). No need to add `department_id` to users again for affiliation; role already ties them to the department.
- Coordinator only sees **academic years** belonging to their department (`AcademicYear::where('department_id', $coordinator->coordinatorDepartmentId())` or equivalent).

---

## 3. Coordinator uploads: Students and Supervisors; both tied to academic year

**Student upload**

- Coordinator selects **Academic year**.
- System assigns: **`role_id`** = student role for that department, **`academic_year_id`** = selected year, **`department_id`** inherited from role (so no duplicate department on users beyond what role implies).
- Insert: `User::create(['role_id' => $studentRoleId, 'academic_year_id' => $selectedYearId, 'index_number' => ..., 'name' => ..., 'email' => ..., 'is_active' => true, ...])`.

**Supervisor upload**

- User created with **role** = supervisor (or **role_id** = supervisor role for department), **academic_year_id**, **department_id** from role.
- Then **`Supervisor::create(['user_id' => $user->id])`** so the supervisor can be assigned to projects later.

Implementation:

- **Add single user** and **Upload**: resolve role via **`getRoleForCoordinatorDepartment($coordinator, 'student'|'supervisor')`**. If role exists: set `user.role_id`, `user.department_id` from role; else fallback to legacy `user.role` + `user.department_id` from coordinator. For supervisors, **`Supervisor::firstOrCreate(['user_id' => $user->id])`** after create/update.

So: both students and supervisors **must** belong to an academic year at upload/create time.

---

## 4. Academic year visually groups students (cards)

- **Data**: Students in coordinator scope are `users` with `role` in (student, group_leader), `department_id` = coordinator’s department, and **`academic_year_id`** set.
- **UI**: “Students” (or “Users”) index can show one section per **academic year**, each section being a **card** (or group of cards) for that year. Within each card: list (or sub-cards) of students in that year.

Implementation:

- Backend: expose users in scope **grouped by `academic_year_id`** (and optionally by `academicYear->year` for labels). E.g. pass `$studentsByAcademicYear` to the view (or equivalent in API).
- Frontend: for each academic year, render a **card**; inside the card, list students (and optionally supervisors) for that year. Existing search/list can remain; the “by year” view is an additional, clear grouping.

---

## 5. Supervisors tied to academic year

- Supervisors are **users** with `role` = supervisor (and optionally a row in `supervisors`).
- **Same as students**: when coordinator adds/updates a supervisor, they set **`users.academic_year_id`** (and `users.department_id`). So supervisors are tied to academic year via **`users.academic_year_id`**.
- No separate `supervisors.academic_year_id` is required; one source of truth on `users` keeps reporting and filtering simple (e.g. “supervisors for this academic year” = users where role = supervisor and `academic_year_id` = X).

---

## 6. Summary

| Requirement | Status | How |
|-------------|--------|-----|
| Coordinator affiliated to school and department | ✅ | `users.department_id` → department → `departments.school_id` → school. |
| Coordinator uploads students | ✅ | Store/upload set `academic_year_id` + `department_id`; role = student (or group_leader). |
| Coordinator uploads supervisors | ✅ | Same flow; role = supervisor. |
| Both belong to academic year | ✅ | Required `academic_year_id` on store/upload; stored on `users`. |
| Academic year groups students (cards) | Defined | Backend: group users by `academic_year_id`; frontend: one card per year, students inside. |
| Supervisors tied to academic year | ✅ | Via `users.academic_year_id` when created/updated by coordinator. |
| `users.academic_year_id` | ✅ | Present and used. |
| `deadlines.academic_year_id` | ✅ | Present and used. |

This aligns the implementation with the described structure. When you add more (e.g. exact card layout or extra filters), it can build on this.

---

## 7. Integrity rules (enforced)

- **Coordinator can only upload users into academic years under their department.**  
  `ensureAcademicYearInScope($coordinator, $academicYearId)` uses `coordinatorDepartmentId()`; aborts 403 if the academic year’s department is not the coordinator’s.

- **Role must belong to the same department.**  
  `getRoleForCoordinatorDepartment($coordinator, 'student'|'supervisor')` returns only roles for the coordinator’s department. Upload/store set `role_id` and `department_id` from that role.

- **Coordinator cannot upload into another department’s academic year.**  
  Same as first point; scope is enforced on every store/upload.

- **Supervisor must exist in both `users` and `supervisors`.**  
  On create/update of a supervisor user, **`Supervisor::firstOrCreate(['user_id' => $user->id])`** is called so they can be assigned to projects.

---

## 8. Permission summary

**Coordinator can:** create students, create supervisors, assign academic year, activate/deactivate users (`is_active`), view counts per academic year, view students/supervisors per year (filtered lists).

**Coordinator cannot:** modify other department’s users (enforced by department scope and `ensureAcademicYearInScope`). Optional: restrict “change user role after creation” in policy if desired.

---

## 9. Final architecture (academic-year centered)

```
School
  → Department
  → Coordinator (role → department)
  → Academic Year
  → Students & Supervisors (per year)
  → Groups
  → Projects
```

- Academic year is the container. Coordinator manages yearly batches. Students and supervisors are grouped by year. Projects and deadlines are tied to academic year. No floating users; everything aligned.
