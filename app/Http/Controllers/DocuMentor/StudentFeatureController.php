<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use App\Models\DocuMentor\Feature;
use App\Models\DocuMentor\Project;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class StudentFeatureController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $user = request()->attributes->get('dm_user');
        if ($project->group->leader_id !== $user->id) {
            abort(403, 'Only Group Leader can add features.');
        }
        if ($project->approved) {
            return back()->with('error', 'Cannot edit project after approval.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Feature::create([
            'name' => $request->name,
            'description' => $request->description,
            'project_id' => $project->id,
        ]);

        return back()->with('success', 'Feature added.');
    }

    public function update(Request $request, Project $project, Feature $feature): RedirectResponse
    {
        $user = request()->attributes->get('dm_user');
        if ($project->group->leader_id !== $user->id || $project->approved) {
            abort(403);
        }
        if ($feature->project_id !== $project->id) {
            abort(404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $feature->update($request->only('name', 'description'));
        return back()->with('success', 'Feature updated.');
    }

    public function destroy(Project $project, Feature $feature): RedirectResponse
    {
        $user = request()->attributes->get('dm_user');
        if ($project->group->leader_id !== $user->id || $project->approved) {
            abort(403);
        }
        // Extra safety: ensure the feature actually belongs to this project before deleting.
        // If it doesn't, try to resolve via the relationship; if still missing, treat as already removed
        // rather than showing an error that blocks the UI.
        if ($feature->project_id !== $project->id) {
            $feature = $project->features()->whereKey($feature->getKey())->first();
            if (! $feature) {
                return back()->with('success', 'Feature already removed.');
            }
        }

        $feature->delete();
        return back()->with('success', 'Feature deleted.');
    }
}
