<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supervisors List</title>
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

        table.data { width: 100%; margin-top: 0; }
        table.data th, table.data td { border: 1px solid #cbd5e1; padding: 5px 7px; }
        table.data tr.head th {
            background: #1f2937;
            color: #ffffff;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            text-align: left;
            font-weight: bold;
        }
        table.data tr.alt td { background: #f8fafc; }
        table.data td.num { text-align: right; }

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
                <div class="report-title">Supervisors List</div>
                <div class="report-subtitle">Coordinator report &mdash; supervisor phone numbers, assigned projects, and student counts</div>
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

    <table class="data">
        <tr class="head">
            <th style="width: 28px;">No.</th>
            <th>Name</th>
            <th style="width: 100px;">Phone</th>
            <th style="width: 72px;">Assigned Projects</th>
            <th style="width: 56px;">Students</th>
        </tr>
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
    </table>

    @if($supervisors->isEmpty())
        <div class="meta" style="margin-top: 12px; text-align: center; font-style: italic;">No supervisors found.</div>
    @else
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
