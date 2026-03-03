# Full Frontend Navigation Map & UI Style Guide

This document is the single source of truth for route-based navigation and design consistency. Give it directly to Cursor or your frontend developer.

---

## Full Frontend Navigation Map (route-based)

### STUDENT / LEADER

| Logical path | Actual route (Laravel) | Notes |
|--------------|------------------------|--------|
| `/dashboard` | `GET /dashboard` | Gateway → Student Dashboard (role student/group_leader) |
| `/dashboard/group` | `GET /dashboard/group/{group}` | `dashboard.group.show` – view my group |
| `/dashboard/group/create` | `GET /dashboard/group/create` | `dashboard.group.create` – create group (leader) |
| `/dashboard/projects` | `GET /dashboard/projects` | `dashboard.projects.index` – my project list |
| `/dashboard/projects/create` | `GET /dashboard/projects/create` | `dashboard.projects.create` – create project (leader) |
| `/dashboard/projects/{id}` | `GET /dashboard/projects/{project}` | `dashboard.projects.show` – project detail |
| `/dashboard/projects/{id}/chapters` | In project show: `#chapters` | Chapters section + per-chapter submission links |
| `/dashboard/projects/{id}/proposals` | In project show: `#proposals` | Proposals section on same page |

**Student group routes:** `dashboard.group.store` (POST), `dashboard.group.add-member`, `dashboard.group.remove-member`.

---

### SUPERVISOR

| Logical path | Actual route (Laravel) | Notes |
|--------------|------------------------|--------|
| `/dashboard` | `GET /dashboard` | Gateway → Supervisor Dashboard (stats + assigned projects table) |
| `/dashboard/projects` | `GET /dashboard/docu-mentor/projects` | `dashboard.docu-mentor.projects.index` – assigned projects |
| `/dashboard/projects/{id}` | `GET /dashboard/docu-mentor/projects/{project}` | `dashboard.docu-mentor.projects.show` – project review (chapters, comments) |
| `/dashboard/submissions/{id}` | Via chapter: `GET /dashboard/docu-mentor/projects/{project}/chapters/{order}` | `dashboard.docu-mentor.chapters.show` – view submissions & comment |
| `/dashboard/comments` | Same as projects; comments live on project/chapter pages | No dedicated /comments list; use project show “Comments” panel |

**Sidebar:** Dashboard · Assigned Projects · Pending Reviews · Messages · Profile.

---

### COORDINATOR

| Logical path | Actual route (Laravel) | Notes |
|--------------|------------------------|--------|
| `/dashboard` | `GET /dashboard` | Gateway → Coordinator Dashboard (stats + project approval table) |
| `/dashboard/projects` | `GET /dashboard/coordinators/projects` | `dashboard.coordinators.projects.index` – all department projects |
| `/dashboard/projects/{id}` | `GET /dashboard/coordinators/projects/{project}` | `dashboard.coordinators.projects.show` – project review (approve/reject, assign supervisor) |
| `/dashboard/deadlines` | `GET /dashboard/coordinators/academic-years` | `dashboard.coordinators.academic-years.index` – manage years & deadlines |
| `/dashboard/categories` | `GET /dashboard/coordinators/categories` | `dashboard.coordinators.categories.index` – project categories |
| `/dashboard/academic-years` | `GET /dashboard/coordinators/academic-years` | Same as deadlines – academic years list |
| `/dashboard/reports` | `GET /dashboard/coordinators/export-report` | `dashboard.coordinators.export-report` – export report |

**Sidebar:** Dashboard · All Projects · Approvals · Supervisors · Deadlines · Categories · Academic Years · Reports · Profile.

---

## UI Design Style Guide

Use consistently across all dashboards.

### Layout

- **Fixed sidebar** – Left sidebar, collapsed state optional (desktop).
- **Top header** – Logo + **Academic year selector** (or display of active year) + Welcome, [Name].
- **Card-based layout** – Main content in cards; avoid long single-column text.
- **Soft shadow** – e.g. `shadow` or `shadow-sm` on cards.
- **White background** – Cards: `bg-white`.
- **Subtle grey sections** – Page background: `bg-gray-50` / `bg-gray-100`; dividers: `border-slate-200`.

### Status Badges

| Status   | Color  | Tailwind (example)     | Use |
|----------|--------|-------------------------|-----|
| Draft    | Blue   | `bg-blue-100 text-blue-800` | Project/document not submitted |
| Pending  | Yellow | `bg-amber-100 text-amber-800` | Awaiting action (approval, review) |
| Approved | Green  | `bg-green-100 text-green-800` | Approved / completed |
| Rejected | Red    | `bg-red-100 text-red-800` | Rejected |

Use the global component: `<x-status-badge :status="..." />` so all badges stay consistent.

### Dashboard Principles

Each dashboard must answer **3 questions immediately**:

**Student**

1. Do I have a group?
2. Do I have a project?
3. What is my status?

→ Use states: No group (Create group) · Group, no project (Create project) · Full project (status, chapters, deadline, supervisor).

**Supervisor**

1. What needs my review?
2. Which submissions are pending?
3. (Implicit) Which projects are assigned to me?

→ Stats: Assigned projects, Pending reviews, Reviewed chapters; table with “View” to project → chapters → submissions & comments.

**Coordinator**

1. What needs approval?
2. How many projects are active?
3. (Implicit) What are deadlines?

→ Stats: Total projects, Pending approval, Approved, Rejected, Active groups; project approval table with “Review”.

---

## System Flow Summary (final)

- **Student** → Creates group (if leader).
- **Group leader** → Creates project.
- **Supervisor** → Reviews submissions (comment, mark reviewed); cannot approve final project.
- **Coordinator** → Approves project (assign supervisor, approve/reject, set status & deadline).

**Out of scope (do not use):** Examiner, Courses, Levels, Quiz.

**Core flow:**

```
Users → Groups → Projects → Chapters → Submissions → Comments → Approval
```

All features align with this flow and the 22-table schema (projects, project_supervisors, project_groups, submissions, chapters, comments, academic_years, etc.).
