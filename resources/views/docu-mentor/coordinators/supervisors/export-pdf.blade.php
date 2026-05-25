<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supervisors List</title>
    <style>
        @page { margin: 130px 28px 50px 28px; }

        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111; margin: 0; padding: 0; }

        /* Fixed header pinned inside the reserved @page top margin */
        #page-header {
            position: fixed;
            top: -110px;
            left: 0;
            right: 0;
            height: 100px;
        }
        #page-header .bar {
            border-bottom: 2px solid #1f2937;
            padding: 0 0 6px 0;
        }
        #page-header table { width: 100%; border-collapse: collapse; }
        #page-header td { vertical-align: middle; padding: 0; }
        #page-header .logo-cell { width: 60px; }
        #page-header .logo-box {
            width: 56px;
            height: 56px;
            overflow: hidden;
            display: block;
        }
        #page-header .logo-box img { display: block; }
        #page-header .institution-name { font-size: 13px; font-weight: bold; color: #0f172a; }
        #page-header .report-title { font-size: 16px; font-weight: bold; color: #0f172a; margin: 2px 0 1px 0; }
        #page-header .report-subtitle { font-size: 9.5px; color: #475569; }
        #page-header .meta-cell {
            width: 130px;
            text-align: right;
            font-size: 10px;
            color: #475569;
        }

        /* Fixed footer pinned inside the reserved @page bottom margin */
        #page-footer {
            position: fixed;
            bottom: -32px;
            left: 0;
            right: 0;
            height: 24px;
            font-size: 9px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
        }
        #page-footer .left { float: left; }
        #page-footer .right { float: right; }

        /* Main content */
        .meta { font-size: 10px; color: #475569; margin: 0 0 10px 0; }
        .meta strong { color: #0f172a; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th,
        table.data td { border: 1px solid #cbd5e1; padding: 5px 8px; }
        table.data thead { display: table-header-group; }
        table.data tfoot { display: table-row-group; }
        table.data thead th {
            background: #1f2937;
            color: #fff;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            text-align: left;
        }
        table.data tbody tr { page-break-inside: avoid; }
        table.data tbody tr.alt td { background: #f8fafc; }
        table.data td.num { text-align: right; }

        .summary { margin-top: 12px; font-size: 10px; color: #334155; }
        .summary span { display: inline-block; margin-right: 14px; }
        .summary strong { color: #0f172a; }

        .empty { padding: 30px; text-align: center; color: #6b7280; font-style: italic; }
    </style>
</head>
<body>
    <div id="page-header">
        <div class="bar">
            <table>
                <tr>
                    <td class="logo-cell">
                        @if(!empty($institutionLogoPath))
                            <div class="logo-box">
                                <img src="{{ $institutionLogoPath }}" alt="Logo" width="56" height="56">
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
    </div>

    <div id="page-footer">
        <span class="left">Generated {{ $reportDate }} &middot; Docu Mento</span>
        <span class="right">
            <script type="text/php">
                if (isset($pdf)) {
                    $pdf->page_script('
                        $text = "Page " . $PAGE_NUM . " of " . $PAGE_COUNT;
                        $font = $fontMetrics->get_font("DejaVu Sans", "normal");
                        $size = 9;
                        $width = $fontMetrics->get_text_width($text, $font, $size);
                        $x = $pdf->get_width() - $width - 28;
                        $y = $pdf->get_height() - 22;
                        $pdf->text($x, $y, $text, $font, $size, [0.4, 0.4, 0.4]);
                    ');
                }
            </script>
        </span>
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
                    <th style="width: 110px;">Phone</th>
                    <th style="width: 78px;">Assigned Projects</th>
                    <th style="width: 60px;">Students</th>
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
</body>
</html>
