# Student Structure & Layout Summary

## 1. Do students have a dashboard?

**Yes.** Students have a dashboard in the current route setup.

| Route | Name | Controller | Purpose |
|-------|------|------------|---------|
| **GET /dashboard** | `dashboard` | `DashboardGatewayController` | Unified entry: students get `StudentDashboardController@index` |
| **GET /student/dashboard** | `student.dashboard` | `StudentController` (delegates to `StudentDashboardController@index`) | Legacy student dashboard URL; same content |

Both URLs end up rendering the same student dashboard (overview with chips, at-a-glance cards, quick access).

---

## 2. Route structure (students)

- **Auth**
  - `GET /student/account/login` → login form (index → phone/OTP or password).
  - `POST /student/account/verify-index`, `send-otp`, `verify-otp` → first-time OTP flow.
  - `GET /student/account/setup` → account setup (first-time after OTP).
  - `POST /student/account/setup` → submit setup (name, phone, password).
  - `POST /student/account/login-password` → subsequent login with password.
  - `POST /student/account/logout` → logout.

- **Dashboard (middleware: `dashboard.auth`, `student.auth`, `student.has-level`)**
  - `GET /dashboard` → gateway → student sees dashboard index.
  - `GET /student/dashboard` → same dashboard index.
  - `GET /dashboard/my-profile` → profile view.
  - `PUT /dashboard/my-profile` → update profile.
  - `GET /dashboard/course-materials` → course materials.
  - `GET /dashboard/calendar` → calendar.
  - `GET /dashboard/documents` → documents.
  - `POST /dashboard/documents` → store document.
  - `GET /dashboard/class-results*` → class results (class rep).

- **Projects (middleware: `dashboard.auth`, `docu-mentor.auth`, `docu-mentor.student`, `docu-mentor.project-access`)**
  - All under `prefix('dashboard')`, `name('dashboard.')`:
  - `GET /dashboard/projects` → list projects.
  - `GET /dashboard/projects/create` → create form.
  - `POST /dashboard/projects` → store project.
  - `GET /dashboard/projects/{project}` → show project.
  - `GET /dashboard/public-projects` → public projects.
  - `GET /dashboard/group/create`, `POST /dashboard/group`, `GET /dashboard/group/{group}` → group create/show and members.

- **Other**
  - `GET /student/select-level` → select level (when level not set).
  - `GET /student/enter-documentor` → bridge into Docu Mentor with redirect.

---

## 3. Layout: how it works

### Base layout

- **Layout:** `resources/views/layouts/student-dashboard.blade.php`
- **Extends:** `layouts.app` (Tailwind, flash messages, meta).
- **Used by:** All student dashboard pages (index, profile, calendar, course-materials, documents) and Docu Mentor student views (projects list/create/show, group show, public projects, class rep).

### Student dashboard layout structure

1. **Header (sticky, amber/yellow)**
   - **Mobile:** Tappable breadcrumb that opens the slide-out sidebar; label = current section (Dashboard, Calendar, Profile, etc.).
   - **Desktop:** Logo “Docu Mento” (link to dashboard), then nav links: Home, Projects (if `$hasProjectAccess`), Class Results (if class rep), then profile menu.
   - **Profile menu:** Avatar, name, index; dropdown: Profile, Log out (student logout → `student.account.logout`).

2. **Mobile sidebar (slide-out)**
   - Opens from header button; overlay closes it.
   - Links: Home, Projects, Class Results (if applicable), Profile.
   - Same routes as desktop nav.

3. **Main content**
   - Constrained width (`max-w-4xl`), padding.
   - On non-home pages: “Back to dashboard” link (desktop).
   - `@yield('dashboard_content')` — each page fills this (overview cards, profile form, calendar, etc.).

### Data passed into the layout

- **View composer:** `AppServiceProvider` registers a composer for `layouts.student-dashboard`.
- **Variables:** `$student`, `$greeting`, `$isClassRep`, `$hasProjectAccess`, `$isGroupLeader`, `$leaderWithoutGroup`, `$leaderHasProject`, `$docuMentorGroup`, `$vapidPublicKey`.
- **Student** comes from `session('student_id')` or `auth()->user()` when it’s a `Student` instance.
- **Docu Mentor flags** (`hasProjectAccess`, `isGroupLeader`, `docuMentorGroup`, etc.) come from the linked `User` (Docu Mentor student/leader) when the student is logged in via session.

---

## 4. Main dashboard view (index)

- **View:** `resources/views/student/dashboard/index.blade.php`
- **Extends:** `layouts.student-dashboard`
- **Section:** `dashboard_content`

**Content:**

- **Header:** Greeting + display name, short subtitle.
- **Chips (horizontal nav):** Overview, Calendar, Materials, Projects (if access), Profile, Class results (if class rep).
- **At a glance:** Cards — Profile; Projects or Class results (if access).
- **Quick access:** Cards for Calendar, Class results (if rep), Projects / Public projects / My group / Create group or Create project (depending on leader/member state).

Links to projects often go through `student.enter-documentor` with a `redirect` query so level/access is checked before showing Docu Mentor project pages.

---

## 5. Other student views (by area)

| View | Layout | Purpose |
|------|--------|---------|
| `student/account-login.blade.php` | `layouts.app` | Login: index → phone → OTP or password step |
| `student/account-setup.blade.php` | `layouts.app` | First-time setup: name, phone, password |
| `student/dashboard/index.blade.php` | `layouts.student-dashboard` | Dashboard overview |
| `student/dashboard/profile.blade.php` | `layouts.student-dashboard` | Profile view/edit |
| `student/dashboard/calendar.blade.php` | `layouts.student-dashboard` | Calendar |
| `student/dashboard/course-materials.blade.php` | `layouts.student-dashboard` | Course materials |
| `student/dashboard/documents.blade.php` | `layouts.student-dashboard` | Documents |
| `student/select-level.blade.php` | `layouts.student-dashboard` | Select level when missing |
| `student/about-system.blade.php` | `layouts.public` | Public “About” page |
| `student/landing.blade.php` | `layouts.app` | Legacy landing (homepage is now `welcome`) |

Docu Mentor student-facing screens (e.g. `docu-mentor/students/projects/index`, `create`, `show`, `group-show`, `public-projects`, `class-rep/index`) also extend `layouts.student-dashboard` so the same header/sidebar and composer data are used.

---

## 6. Flow summary

1. **Public:** Homepage `welcome` (single hero) → Student login or About.
2. **Login:** Index → first-time: OTP → Account setup → then dashboard; returning: password → dashboard.
3. **After login:** `/dashboard` or `/student/dashboard` → `StudentDashboardController@index` → `student.dashboard.index` with `layouts.student-dashboard`.
4. **Dashboard:** Same layout for Overview, Profile, Calendar, Materials, Documents, Class results; project/group links go through Docu Mentor middleware and (when needed) `student.enter-documentor`.
5. **Projects:** Under `/dashboard` (projects, group, public-projects) use the same student-dashboard layout and Docu Mentor controllers.

---

## 7. Fix applied

- **Missing controller:** `App\Http\Controllers\Student\StudentDashboardController` was referenced in routes and `DashboardGatewayController` but the file was missing. It has been added with:
  - `index()` → `student.dashboard.index` (dashboard overview)
  - `profile()`, `updateProfile()`, `courseMaterials()`, `calendar()` for the corresponding dashboard routes.
- **View composer:** `docuMentorGroup` is now included in the `layouts.student-dashboard` composer so “My group” and related logic receive the variable correctly.

Students therefore have a working dashboard at the current routes (`/dashboard` and `/student/dashboard`) with the layout and structure described above.
