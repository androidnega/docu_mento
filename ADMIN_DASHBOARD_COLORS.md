# Admin Dashboard Color System

Structured, professional, no gradients. Amber accent only where needed. Contrast with student UI (amber header, friendly); admin = dark sidebar, strong layout.

---

## Core palette

| Use | Class | Hex |
|-----|--------|-----|
| **Primary accent** | `amber-500` | #F59E0B |
| **Accent hover** | `amber-600` | #D97706 |
| **Active bg (sidebar)** | `amber-50` | #FFFBEB |
| **Page background** | `bg-gray-100` | #F3F4F6 |
| **Sidebar** | `bg-gray-900` | #111827 |
| **Sidebar text** | `text-gray-300` | #D1D5DB |
| **Sidebar active** | `bg-gray-800` + `border-l-4 border-amber-500` | #1F2937 + amber |
| **Header** | `bg-white` `border-b border-gray-200` | — |
| **Cards** | `bg-white` `border border-gray-200` `shadow-sm` | — |

---

## Where amber is used

- Active sidebar item (left border only).
- Primary buttons (Save, Create, Approve, Schools quick link).
- Header badges (SMS / AI tokens): `bg-amber-100 text-amber-700`.
- Small highlights (e.g. one stat number on admin home).
- Focus rings: `focus:ring-amber-500`.

Do **not** make the whole admin UI amber.

---

## Layout

- **Page:** `bg-gray-100`.
- **Sidebar:** `bg-gray-900`, `border-r border-gray-800`. Section labels: `text-gray-500`.
- **Header:** `bg-white`, `border-b border-gray-200`. Title: `text-gray-800`.
- **Main:** `bg-gray-100`.

---

## Cards

- Base: `bg-white rounded-lg shadow-sm border border-gray-200`.
- Hover (when clickable): `hover:shadow-md transition-shadow`.
- Stat title: `text-gray-500 text-xs font-medium uppercase tracking-wide`.
- Stat number: `text-2xl font-bold text-gray-900` (optional accent: `text-amber-600`).

---

## Buttons

- **Primary:** `bg-amber-500 hover:bg-amber-600 text-white`.
- **Secondary:** `bg-gray-200 hover:bg-gray-300 text-gray-800`.
- **Danger:** `bg-red-600 hover:bg-red-700 text-white` (Delete, Reset, Revoke only).

---

## Forms

- Inputs: `bg-white border border-gray-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500`.
- Labels: `text-gray-700 text-sm font-medium`.
- Error: `text-red-600`.

---

## Tables

- Header: `bg-gray-50 text-gray-600 border-b border-gray-200`.
- Rows: `bg-white hover:bg-gray-50`.
- Row border: `border-b border-gray-100`.

---

## Badges

- Success: `bg-green-100 text-green-700`.
- Warning: `bg-amber-100 text-amber-700`.
- Danger: `bg-red-100 text-red-700`.
- Info: `bg-blue-100 text-blue-700`.

---

## Breadcrumb

- Text: `text-gray-500`, hover: `hover:text-gray-700`.
- Separator: `text-gray-400`.

---

## Sidebar nav (implementation)

- Default link: `text-gray-300` (via `.examiner-nav-link`).
- Icon: `text-gray-400` (via `.examiner-nav-icon`).
- Hover: `bg-gray-800 text-white` (icon inherits).
- Active: `bg-gray-800 border-l-4 border-amber-500 text-white`; icon: `text-amber-500`.

---

## Files updated

- `resources/views/layouts/dashboard.blade.php` — sidebar (gray-900, nav states), main bg-gray-100, header badges, breadcrumb.
- `resources/views/admin/dashboard-admin.blade.php` — stat cards, quick links (primary/secondary/danger).
- `resources/views/admin/dashboard-examiner.blade.php` — notice (amber), low SMS (red-100), project card (white + amber icon).

Use this doc when adding or changing admin dashboard views so the system stays consistent.
