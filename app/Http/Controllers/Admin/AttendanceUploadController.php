<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\AttendanceUploadLog;
use App\Models\ValidIndex;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AttendanceUploadController extends Controller
{
    use InteractsWithAdminSession;

    public const UPLOAD_MODE_REPLACE = 'replace';
    public const UPLOAD_MODE_MERGE = 'merge';
    /**
     * Show attendance page: add single index + upload Excel.
     */
    public function index(): View
    {
        return view('admin.attendance.index');
    }

    /**
     * Add a single valid index (index_number + optional student_name).
     */
    public function addSingle(Request $request): RedirectResponse
    {
        $request->validate([
            'index_number' => 'required|string|max:64',
            'student_name' => 'nullable|string|max:255',
        ]);
        $indexNumber = trim($request->index_number);
        $studentName = $request->filled('student_name') ? trim($request->student_name) : null;
        ValidIndex::updateOrCreate(
            ['index_number' => $indexNumber],
            ['student_name' => $studentName]
        );
        return redirect()->route('admin.attendance.index')->with('success', 'Index added.');
    }

    /**
     * Upload Excel for attendance → populate valid_indices.
     * Expected columns: index_number, student_name (optional), or first column = index, second = name.
     * Mode: replace = delete all valid indices then insert; merge = updateOrCreate per row.
     * Logs uploader, timestamp, mode, and row counts.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'upload_mode' => 'required|in:' . self::UPLOAD_MODE_REPLACE . ',' . self::UPLOAD_MODE_MERGE,
        ]);
        $uploadMode = $request->input('upload_mode', self::UPLOAD_MODE_REPLACE);
        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        $header = array_shift($rows);
        $indexCol = 0;
        $nameCol = 1;
        foreach ($header as $i => $h) {
            $h = is_string($h) ? strtolower($h) : '';
            if (str_contains($h, 'index') || $i === 0) {
                $indexCol = $i;
            }
            if (str_contains($h, 'name') || str_contains($h, 'student')) {
                $nameCol = $i;
            }
        }
        $byIndex = [];
        foreach ($rows as $row) {
            $index = trim((string) ($row[$indexCol] ?? ''));
            if ($index === '') {
                continue;
            }
            $name = isset($row[$nameCol]) ? trim((string) $row[$nameCol]) : null;
            $byIndex[$index] = $name;
        }

        $user = $this->adminUser();
        $rowsAdded = 0;
        $rowsUpdated = 0;
        $rowsDeleted = 0;

        if ($uploadMode === self::UPLOAD_MODE_REPLACE) {
            $rowsDeleted = ValidIndex::count();
            ValidIndex::query()->delete();
            foreach ($byIndex as $index => $name) {
                ValidIndex::create([
                    'index_number' => $index,
                    'student_name' => $name,
                ]);
                $rowsAdded++;
            }
        } else {
            foreach ($byIndex as $index => $name) {
                $existing = ValidIndex::where('index_number', $index)->first();
                ValidIndex::updateOrCreate(
                    ['index_number' => $index],
                    ['student_name' => $name]
                );
                if ($existing) {
                    $rowsUpdated++;
                } else {
                    $rowsAdded++;
                }
            }
        }

        AttendanceUploadLog::create([
            'class_group_id' => null,
            'uploaded_by' => $user?->id,
            'upload_mode' => $uploadMode,
            'rows_added' => $rowsAdded,
            'rows_updated' => $rowsUpdated,
            'rows_deleted' => $rowsDeleted,
            'uploaded_at' => now(),
        ]);

        $message = $uploadMode === self::UPLOAD_MODE_REPLACE
            ? "Replaced valid indices: {$rowsAdded} indices (removed {$rowsDeleted} previous)."
            : "Merged {$rowsAdded} new and {$rowsUpdated} updated indices.";
        return redirect()->route('admin.attendance.index')->with('success', $message);
    }
}
