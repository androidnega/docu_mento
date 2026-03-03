<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\ClassGroup;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Study guide: any super admin can view cohort material via a time-limited signed URL.
 * No logging in the database.
 */
class StudyGuideController extends Controller
{
    use InteractsWithAdminSession;

    public function __invoke(Request $request, ClassGroup $classGroup): View|Response
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('class_groups')) {
            abort(404, 'Study guide not available.');
        }

        $user = $this->adminUser();
        $isSuperAdmin = $user && $user->isSuperAdmin();

        if (! $isSuperAdmin) {
            abort(403, 'Access denied.');
        }

        if (! session('study_guide_unlocked', false)) {
            abort(403, 'Unlock study guide in Settings → Study guide first.');
        }

        $classGroup->load([]);

        return view('admin.study-guide.show', compact('classGroup'));
    }
}
