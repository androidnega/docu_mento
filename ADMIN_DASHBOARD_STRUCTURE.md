# Admin Dashboard Structure — Clean Role-Based Flow

The dashboard is a **unified** layout at `/dashboard`. Entry: `GET /dashboard` → `DashboardGatewayController`, which renders the correct view by **role**. Single authentication: all users log in via the **users** table (Laravel Auth). No `student_id` or `admin_user_id` session; access is controlled by **roles** and **policies**.

---

## 1. Role structure (aligned with schema)

Standardized role names (from `roles.name` or legacy `users.role` mapping):

- **student**
- **group_leader**
- **supervisor**
- **coordinator**
- **admin** (optional system-level)

Every user has one role (via `role_id` or legacy `role` column). `User::roleName()` returns the canonical name for middleware and layout.

---

## 2. Who sees what

| Role            | Landing view / behaviour |
|-----------------|--------------------------|
| **student** / **group_leader** | Student dashboard (`StudentDashboardController::index`) |
| **supervisor**  | Examiner/Supervisor dashboard (`AdminDashboardController::examinerDashboard`) |
| **coordinator** | Coordinator dashboard (`CoordinatorController::dashboard`) |
| **admin**       | Admin dashboard (`AdminDashboardController::index`) |

Layout: `resources/views/layouts/dashboard.blade.php` (sidebar + header + main content). Role flags in layout use `auth()->user()->roleName()` (no session role keys).

---

## 3. Authentication and middleware

- **Login:** Single `/login` (username/email/phone + password). `AdminAuthController` uses `Auth::login($user)`.
- **Logout:** `Auth::logout()`; session invalidated and token regenerated.
- **Middleware:**
  - `auth` — Laravel `Authenticate` (require logged-in user).
  - `admin.auth` — `EnsureAdminAuthenticated`: require auth, restrict coordinator to coordinator/supervisor routes only.
  - `role:student,group_leader` — `EnsureRole`: allow only listed roles (e.g. student projects).
  - `docu-mentor.auth` — require auth + Docu Mentor–allowed role; sets `dm_user` on request.
  - `docu-mentor.project-access` — policy-based project access.

No `session('student_id')`, `session('admin_user_id')`, or `enter-documentor` bridge. Student account login (index + OTP / password) finds or creates a **User** and calls `Auth::login($user)`.

---

## 4. Layout role flags

In `layouts/dashboard.blade.php`:

- `$roleName` = `auth()->user()->roleName()`
- `$isSuperAdmin` — role is `super_admin` (legacy) or admin
- `$isExaminer` — roleName is `supervisor`
- `$isCoordinatorOnly` — roleName is `coordinator`
- `$isDocuMentorCoordinator` — coordinator or admin
- `$isDocuMentorStudent` — student or group_leader

Legacy sidebar links removed; Docu Mentor only.

---

## 5. Sidebar by role

### Coordinator only

- Dashboard, Docu Mento: Students, Project Categories, Project Groups, Assign Group Leaders, Projects, Supervisor Workload.

### Supervisor (examiner)

- Dashboard, Docu Mentor (projects).

### Student / group leader

- Dashboard, Project, My Projects, Public Projects.

### Super Admin

- Dashboard, Schools, Users, Settings, Reset.

---

## 6. Route tree (under `dashboard.`)

- **Dashboard:** `GET /dashboard` — `auth` middleware; gateway dispatches by role.
- **Student/group_leader:** `auth` + `role:student,group_leader` for my-profile, calendar, documents, **projects**, **group** (create, add/remove member), submissions.
- **Staff:** under `admin.auth` + `block.superadmin.coordinator`: ping, profile, coordinators.*, docu-mentor.* (supervisor), admin.role (schools, users, settings, reset).

Project access is enforced by **policies** (e.g. `ProjectPolicy::view`) using `group_members`, `project_supervisors`, and department.

---

## 7. Removed / deprecated

- `student_id` session.
- `admin_user_id` / `admin_authenticated` / `admin_role` session for auth (replaced by Laravel Auth).
- `student.enter-documentor` route and controller.
- Branching and dual login system.
- `student.has-level` middleware (no-op; access is database-driven).

---

## 8. Controllers (summary)

| Area        | Controller |
|------------|------------|
| Gateway    | `DashboardGatewayController` |
| Login/Logout | `Admin\AdminAuthController` |
| Admin/Examiner home | `Admin\AdminDashboardController` |
| Coordinator home | `DocuMentor\CoordinatorController` |
| Student home | `Student\StudentDashboardController` |
| Profile, Users, Schools, Settings, Reset | `Admin\*` |
| Coordinator: academic years, categories, groups, projects, students | `DocuMentor\*` |
| Supervisor: projects, chapters, submissions, files, AI | `DocuMentor\Supervisor*` |

This is the current structure: single auth, role-based dashboard, policies for project/group access, no dual identity or session role keys.
