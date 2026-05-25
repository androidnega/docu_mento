<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supervisors Report</title>
    <style>
        @page { margin: 22px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111; margin: 0; padding: 0; }

        table { border-collapse: collapse; }
        td, th { padding: 0; }

        .doc-header td { vertical-align: middle; padding: 0; }
        .institution-name { font-size: 12px; font-weight: bold; color: #0f172a; }
        .report-title { font-size: 16px; font-weight: bold; color: #0f172a; margin: 1px 0; }
        .report-subtitle { font-size: 9px; color: #475569; }
        .meta-cell { text-align: right; font-size: 9px; color: #475569; }

        .meta { font-size: 9.5px; color: #475569; margin: 0 0 8px 0; }
        .meta strong { color: #0f172a; }

        .supervisor-block { margin-top: 10px; }
        .supervisor-name {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            text-decoration: underline;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 3px;
            margin-bottom: 5px;
        }
        .supervisor-meta { width: 100%; font-size: 10px; color: #334155; margin-bottom: 6px; }
        .supervisor-meta td { padding: 2px 8px 2px 0; vertical-align: top; }
        .supervisor-meta .label { color: #475569; font-weight: bold; width: 30%; }
        .supervisor-meta .value { color: #0f172a; }

        .project-card {
            margin: 6px 0 6px 0;
            border: 1px solid #e2e8f0;
            border-left: 3px solid #1f2937;
            padding: 6px 8px;
            background: #f8fafc;
        }
        .project-title {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .project-title .num {
            color: #475569;
            font-weight: normal;
            font-size: 10px;
            margin-right: 4px;
        }
        .students-label {
            font-size: 9.5px;
            font-weight: bold;
            color: #334155;
            margin-top: 2px;
        }
        table.students { width: 100%; font-size: 10px; margin: 2px 0 4px 0; }
        table.students td, table.students th { padding: 2px 6px; border-bottom: 1px solid #eef2f7; vertical-align: top; }
        table.students th { background: #f1f5f9; color: #475569; font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.3px; text-align: left; font-weight: bold; }
        table.students th.num, table.students td.num { text-align: right; }
        table.students td.idx { width: 22px; color: #64748b; text-align: right; }
        table.students td.index-no { width: 90px; color: #0f172a; font-family: DejaVu Sans Mono, monospace; font-size: 9.5px; }
        table.students td.phone { width: 110px; color: #475569; }
        table.students tr.leader td { background: #fff7ed; }
        table.students tr.leader td.name strong { color: #b45309; }
        .leader-tag {
            display: inline-block;
            background: #b45309;
            color: #fff;
            font-size: 8px;
            padding: 1px 5px;
            border-radius: 3px;
            margin-left: 4px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .leader-line {
            font-size: 9.5px;
            color: #334155;
            margin-top: 3px;
            padding: 3px 6px;
            background: #fff7ed;
            border-left: 2px solid #b45309;
        }
        .leader-line strong { color: #92400e; }

        .no-projects {
            font-size: 10px;
            color: #6b7280;
            font-style: italic;
            padding: 4px 0;
        }

        .divider { border-top: 1px dashed #cbd5e1; margin: 12px 0; }

        .summary {
            margin-top: 10px;
            font-size: 9.5px;
            color: #334155;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
        }
        .summary span { margin-right: 14px; }
        .summary strong { color: #0f172a; }

        .doc-footer {
            margin-top: 14px;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
            font-size: 8.5px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>
<body>
    <table class="doc-header" style="width: 100%; border-bottom: 2px solid #1f2937; padding-bottom: 6px; margin-bottom: 8px;">
        <tr>
            <td style="width: 56px;">
                @if(!empty($institutionLogoPath))
                    <img src="{{ $institutionLogoPath }}" alt="Logo" width="50" height="50">
                @endif
            </td>
            <td>
                @if(!empty($institutionName))
                    <div class="institution-name">{{ $institutionName }}</div>
                @endif
                <div class="report-title">Supervisors Report</div>
                <div class="report-subtitle">Supervisor contact details, assigned projects, students &amp; group leaders</div>
            </td>
            <td class="meta-cell" style="width: 120px;">
                <div><strong>Date:</strong> {{ $reportDate }}</div>
                <div><strong>Total:</strong> {{ $supervisors->count() }}</div>
            </td>
        </tr>
    </table>

    <div class="meta">
        @if(!empty($coordinatorName))
            <strong>Coordinator:</strong> {{ $coordinatorName }} &nbsp;|&nbsp;
        @endif
        @if(!empty($departmentName))
            <strong>Department:</strong> {{ $departmentName }} &nbsp;|&nbsp;
        @endif
        @if(!empty($searchTerm))
            <strong>Search:</strong> "{{ $searchTerm }}" &nbsp;|&nbsp;
        @endif
        @if(!empty($projectsFilterLabel))
            <strong>Filter:</strong> {{ $projectsFilterLabel }}
        @endif
    </div>

    @php
        $totalProjects = 0;
        $totalStudents = 0;
    @endphp

    @if($supervisors->isEmpty())
        <div class="meta" style="margin-top: 12px; text-align: center; font-style: italic;">No supervisors found.</div>
    @else
        @foreach($supervisors as $supIdx => $sup)
            @php
                $projects = $sup->supervisedProjects ?? collect();
                $projectCount = $projects->count();
                $studentCount = (int) ($sup->total_students_count ?? 0);
                $totalProjects += $projectCount;
                $totalStudents += $studentCount;
            @endphp

            <div class="supervisor-block">
                <div class="supervisor-name">{{ ($supIdx + 1) }}. {{ $sup->name ?? '—' }}</div>

                <table class="supervisor-meta">
                    <tr>
                        <td class="label">Phone Number:</td>
                        <td class="value">{{ $sup->phone ?: '—' }}</td>
                        <td class="label" style="width: 30%;">Number of Students:</td>
                        <td class="value">{{ $studentCount }}</td>
                    </tr>
                    <tr>
                        <td class="label">Number of Projects:</td>
                        <td class="value">{{ $projectCount }}</td>
                        <td class="label">Email:</td>
                        <td class="value">{{ $sup->email ?: '—' }}</td>
                    </tr>
                </table>

                @if($projectCount === 0)
                    <div class="no-projects">No projects assigned to this supervisor.</div>
                @else
                    @foreach($projects as $projIdx => $project)
                        @php
                            $group = $project->group;
                            $members = $group?->members ?? collect();
                            $leaderId = $group?->leader_id;
                            $leader = $leaderId ? $members->firstWhere('id', $leaderId) : null;
                        @endphp
                        <div class="project-card">
                            <div class="project-title">
                                <span class="num">Project {{ $projIdx + 1 }}:</span>
                                {{ $project->title ?? 'Untitled project' }}
                            </div>

                            <div class="students-label">
                                Students &nbsp;<span style="font-weight: normal; color: #64748b;">({{ $members->count() }})</span>
                            </div>

                            @if($members->isEmpty())
                                <div class="no-projects" style="padding: 2px 0;">No students in this project group.</div>
                            @else
                                <table class="students">
                                    <tr>
                                        <th class="num" style="width: 22px;">#</th>
                                        <th style="width: 90px;">Index No.</th>
                                        <th>Name</th>
                                        <th style="width: 110px;">Phone</th>
                                    </tr>
                                    @foreach($members as $mIdx => $member)
                                        @php $isLeader = $leaderId && (int) $member->id === (int) $leaderId; @endphp
                                        <tr @class(['leader' => $isLeader])>
                                            <td class="idx">{{ $mIdx + 1 }}.</td>
                                            <td class="index-no">{{ $member->index_number ?: '—' }}</td>
                                            <td class="name">
                                                @if($isLeader)<strong>{{ $member->name ?? '—' }}</strong>@else{{ $member->name ?? '—' }}@endif
                                                @if($isLeader)<span class="leader-tag">Group Leader</span>@endif
                                            </td>
                                            <td class="phone">{{ $member->phone ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endif

                            @if($leader)
                                <div class="leader-line">
                                    <strong>Group Leader:</strong> {{ $leader->name ?? '—' }}
                                    @if($leader->index_number)
                                        ({{ $leader->index_number }})
                                    @endif
                                    &mdash; {{ $leader->phone ?: 'No phone on file' }}
                                </div>
                            @elseif(! $members->isEmpty())
                                <div class="leader-line" style="background: #fef2f2; border-left-color: #b91c1c;">
                                    <strong style="color: #991b1b;">Group Leader:</strong> Not assigned for this project.
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>

            @if(! $loop->last)
                <div class="divider"></div>
            @endif
        @endforeach
    @endif

    <div class="summary">
        <span><strong>Supervisors:</strong> {{ $supervisors->count() }}</span>
        <span><strong>Total Assigned Projects:</strong> {{ $totalProjects }}</span>
        <span><strong>Total Students:</strong> {{ $totalStudents }}</span>
    </div>

    <div class="doc-footer">
        Generated {{ $reportDate }} &middot; Docu Mento
    </div>
</body>
</html>
