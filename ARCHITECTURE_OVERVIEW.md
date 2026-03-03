# Docu_Mento — Final Architectural View

## Six-Layer Architecture

| Layer | Name | Flow | Tables / Concepts |
|-------|------|------|-------------------|
| **1** | Academic Structure | Schools → Departments → Academic Years | `schools`, `departments`, `academic_years` |
| **2** | Identity & Authorization | Roles → Users → Supervisors | `roles`, `users`, `contacts`, `supervisors` |
| **3** | Collaboration | Project Groups → Members | `groups`, `group_members` |
| **4** | Core Project Engine | Projects → Supervisors → Features → Status | `categories`, `deadlines`, `project_statuses`, `projects`, `project_supervisors`, `features` |
| **5** | Document Management | Files → Proposals → Chapters → Submissions → AI → Comments | `files`, `proposals`, `chapters`, `submissions`, `comments`, `ai_generations` |
| **6** | Communication | SMS Logs → Status | `sms_statuses`, `sms_logs` (Contact → SMS Log → Status) |

---

## Recommended Development Timeline

| Phase | Focus | Complexity | Priority |
|-------|--------|------------|----------|
| 1 | Core hierarchy (Schools, Departments, Academic Years) | Low | Highest |
| 2 | Users & roles (Roles, Users, Contacts, Supervisors) | Medium | Highest |
| 3 | Groups (Project Groups, Members) | Medium | High |
| 4 | Projects (Categories, Deadlines, Status, Projects, Supervisors, Features) | High | Critical |
| 5 | Submissions (Files, Proposals, Chapters, Submissions, Comments, AI) | Very High | Critical |
| 6 | SMS (Status, Logs, Contact linkage) | Low | Optional last |

---

## Schema Evaluation

### Strengths

- **Excellent normalization** — Clear entities, minimal redundancy.
- **Proper cascade vs restrict** — e.g. restrict on role/user when in use; cascade on contact delete for SMS logs.
- **Strong unique constraints** — Per-school department names, per-department academic years, per-chapter submission per user, proposal versioning.
- **Clean separation of concerns** — Each layer has a clear responsibility.
- **AI-ready** — `ai_generations` / `document_ai_reviews` with JSON storage.
- **Version control** — Proposal versioning and file abstraction.
- **Self-referencing project hierarchy** — `parent_project_id` on projects.

### Minor Improvements (Optional)

- Add performance indexes where needed (e.g. `academic_year_id`, `deadline_id`, `approved_by`, `project_id` on proposals — many already present).
- Consider soft deletes for audit safety (implemented on proposals and submissions).
- Consider auditing fields (e.g. `created_by`, `updated_by`) later.

---

## Conclusion

The Docu_Mento database is:

- **Scalable** — Layered design and indexes support growth.
- **Enterprise-ready** — Multi-school, roles, and clear boundaries.
- **Cleanly layered** — Academic → Identity → Collaboration → Projects → Documents → Communication.
- **Extensible** — Status lookups, JSON for AI, versioned proposals.

---

## Reference

- **Table and column details:** see `SYSTEM_STRUCTURE.txt`.
- **Migrations:** `database/migrations/` (Phase 1–6 and SMS notification tracking).

---

## Possible Next Steps

1. **Relationship flow diagram** — Visual ER or layer diagram.
2. **Eloquent models** — Align models with this layering and relations.
3. **Role permissions architecture** — Gates, policies, and role–department scoping.
4. **Multi-school SaaS** — Tenant scoping, indexes, and config.
5. **Production ERD** — Narrative or diagram for deployment and onboarding.
