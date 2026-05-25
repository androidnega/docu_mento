<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supervisors List</title>
    <style>
        @page { margin: 24px 24px 24px 24px; }

        html, body { margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111; }

        .doc-header {
            border-bottom: 2px solid #1f2937;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .doc-header table { width: 100%; border-collapse: collapse; }
        .doc-header td { vertical-align: middle; padding: 0; }
        .doc-header .logo-cell { width: 60px; }
        .doc-header .logo-box {
            width: 50px;
            height: 50px;
            overflow: hidden;
        }
        .doc-header .institution-name { font-size: 12px; font-weight: bold; color: #0f172a; }
        .doc-header .report-title { font-size: 15px; font-weight: bold; color: #0f172a; margin: 1px 0; }
        .doc-header .report-subtitle { font-size: 9px; color: #475569; }
        .doc-header .meta-cell {
            width: 120px;
            text-align: right;
            font-size: 9px;
            color: #475569;
        }

        .meta { font-size: 9.5px; color: #475569; margin: 0 0 8px 0; }
        .meta strong { color: #0f172a; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th,
        table.data td { border: 1px solid #cbd5e1; padding: 4px 7px; }
        table.data thead { display: table-header-group; }
        table.data thead th {
            background: #1f2937;
            color: #fff;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            text-align: left;
        }
        table.data tbody tr { page-break-inside: avoid; }
        table.data tbody tr.alt td { background: #f8fafc; }
        table.data td.num { text-align: right; }

        .summary {
            margin-top: 10px;
            font-size: 9.5px;
            color: #334155;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
        }
        .summary span { display: inline-block; margin-right: 14px; }
        .summary strong { color: #0f172a; }

        .doc-footer {
            margin-top: 14px;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
            font-size: 8.5px;
            color: #6b7280;
            text-align: center;
        }

        .empty { padding: 30px; text-align: center; color: #6b7280; font-style: italic; }
    </style>
</head>
<body>
    <div class="doc-header">
        <table>
            <tr>
                <td class="logo-cell">
                    @if(!empty($institutionLogoPath))
                        <div class="logo-box">
                            <img src="{{ $institutionLogoPath }}" alt="Logo" width="50" height="50">
                        </div>
                    @endif
                </td>
                <td>
                    @if(!empty($institutionName))
                        <div class="institution-name">{{ $institutionName }}</div>
                    @endif
                    <div class="report-title">Supervisors List</div>
                    <div class="report-subtitle">Coordinator report &mdash; supervisor phone numbers, assigned projects, and student counts</div>
                </td>
                <td class="meta-cell">
                    <div><strong>Date:</strong> {{ $reportDate }}</div>
                    <div><strong>Total:</strong> {{ $supervisors->count() }}</div>
                </td>
            </tr>
        </table>
    </div>

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

    @if($supervisors->isEmpty())
        <div class="empty">No supervisors found.</div>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 28px;">No.</th>
                    <th>Name</th>
                    <th style="width: 100px;">Phone</th>
                    <th style="width: 72px;">Assigned Projects</th>
                    <th style="width: 56px;">Students</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalProjects = 0;
                    $totalStudents = 0;
                @endphp
                @foreach($supervisors as $i => $sup)
                    @php
                        $projects = (int) ($sup->supervised_projects_count ?? 0);
                        $students = (int) ($sup->total_students_count ?? 0);
                        $totalProjects += $projects;
                        $totalStudents += $students;
                    @endphp
                    <tr @class(['alt' => $i % 2 === 1])>
                        <td class="num">{{ $i + 1 }}</td>
                        <td>{{ $sup->name ?? '—' }}</td>
                        <td>{{ $sup->phone ?? '—' }}</td>
                        <td class="num">{{ $projects }}</td>
                        <td class="num">{{ $students }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            <span><strong>Supervisors:</strong> {{ $supervisors->count() }}</span>
            <span><strong>Total Assigned Projects:</strong> {{ $totalProjects }}</span>
            <span><strong>Total Students:</strong> {{ $totalStudents }}</span>
        </div>
    @endif

    <div class="doc-footer">
        Generated {{ $reportDate }} &middot; Docu Mento
    </div>
</body>
</html>
