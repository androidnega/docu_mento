<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supervisors List</title>
    <style>
        @page { margin: 28px 28px 50px 28px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111; }
        .header { width: 100%; border-bottom: 2px solid #1f2937; padding-bottom: 8px; margin-bottom: 14px; }
        .header-table { width: 100%; }
        .header-table td { vertical-align: middle; }
        .institution-logo { max-height: 56px; max-width: 56px; }
        .institution-name { font-size: 14px; font-weight: bold; color: #0f172a; }
        .report-title { font-size: 16px; font-weight: bold; color: #0f172a; margin: 0; }
        .report-subtitle { font-size: 10px; color: #475569; margin-top: 2px; }
        .meta { font-size: 10px; color: #475569; margin: 6px 0 12px 0; }
        .meta strong { color: #0f172a; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid #cbd5e1; padding: 6px 8px; }
        table.data thead th { background: #1f2937; color: #fff; font-size: 10px; text-transform: uppercase; letter-spacing: 0.4px; text-align: left; }
        table.data tbody tr:nth-child(even) { background: #f8fafc; }
        table.data td.num { text-align: right; font-variant-numeric: tabular-nums; }
        .summary { margin-top: 14px; font-size: 10px; color: #334155; }
        .summary span { display: inline-block; margin-right: 14px; }
        .summary strong { color: #0f172a; }
        .empty { padding: 30px; text-align: center; color: #6b7280; font-style: italic; }
        .footer { position: fixed; bottom: 10px; left: 28px; right: 28px; font-size: 9px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 4px; }
        .footer .left { float: left; }
        .footer .right { float: right; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 60px;">
                    @if(!empty($institutionLogoPath))
                        <img src="{{ $institutionLogoPath }}" alt="Logo" class="institution-logo">
                    @endif
                </td>
                <td>
                    @if(!empty($institutionName))
                        <div class="institution-name">{{ $institutionName }}</div>
                    @endif
                    <p class="report-title">Supervisors List</p>
                    <div class="report-subtitle">Coordinator report &mdash; supervisor phone numbers, assigned projects, and student counts</div>
                </td>
                <td style="text-align: right; font-size: 10px; color: #475569;">
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
                    <th style="width: 32px;">No.</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th style="width: 90px;">Assigned Projects</th>
                    <th style="width: 70px;">Students</th>
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
                    <tr>
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

    <div class="footer">
        <span class="left">Generated {{ $reportDate }} &middot; Docu Mento</span>
        <span class="right">Page <script type="text/php">if (isset($pdf)) { $pdf->page_text(520, 815, "{PAGE_NUM} / {PAGE_COUNT}", null, 9, [0.4,0.4,0.4]); }</script></span>
    </div>
</body>
</html>
