@extends('docu-mentor.layout')

@section('title', $project->title . ' – Docu Mentor')

@section('content')
@php
    $canGrade = $project->canSupervisorsGrade();
    $currentUserApproval = $project->supervisorApprovals->firstWhere('user_id', $user->id);
    $currentUserHasApproved = $currentUserApproval && ($currentUserApproval->approved_at || $currentUserApproval->approved);
@endphp
<div class="max-w-6xl mx-auto w-full pt-4 sm:pt-6">
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <a href="{{ route('dashboard.docu-mentor.projects.index') }}" class="text-indigo-600 hover:text-indigo-800 text-sm mb-3 inline-flex items-center gap-1">
            <span aria-hidden="true">←</span>
            <span>Assigned projects</span>
        </a>
        <h1 class="text-2xl font-bold text-slate-900">{{ $project->title }}</h1>
        <p class="text-slate-500 text-sm mt-1">
            Group: {{ $project->group?->name ?? '—' }} · {{ $project->academicYear?->year ?? '—' }}
            @if($project->category)
                · <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-indigo-50 text-indigo-700">{{ $project->category->name }}</span>
            @endif
        </p>
    </div>
    @if($canGrade && $project->group && $project->group->members->isNotEmpty())
        <a href="#grade-students" class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 shrink-0 mt-2 sm:mt-3">
            Grade students
        </a>
    @endif
</div>

{{-- PROJECT DETAIL --}}
<section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-6">
    <h2 class="text-sm font-semibold text-slate-800 uppercase tracking-wide mb-4">Project detail</h2>
    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
        <div>
            <dt class="text-slate-500 font-medium">Project</dt>
            <dd class="text-slate-900 font-semibold mt-0.5">{{ $project->title }}</dd>
        </div>
        <div>
            <dt class="text-slate-500 font-medium">Group</dt>
            <dd class="text-slate-900 mt-0.5">{{ $project->group?->name ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-slate-500 font-medium">Status</dt>
            <dd class="mt-0.5">
                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $project->approved ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                    {{ $project->approved ? 'Active' : 'Pending' }}
                </span>
            </dd>
        </div>
        <div>
            <dt class="text-slate-500 font-medium">Deadline</dt>
            <dd class="text-slate-900 mt-0.5">{{ ($project->submission_deadline ?? $project->academicYear?->effective_deadline)?->format('j F Y') ?? '—' }}</dd>
        </div>
    </dl>
</section>

{{-- MEMBERS --}}
@if($project->group && $project->group->members->isNotEmpty())
<section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-6">
    <h2 class="text-sm font-semibold text-slate-800 uppercase tracking-wide mb-3">Members</h2>
    <ul class="space-y-1.5">
        @foreach($project->group->members as $member)
            <li class="text-sm text-slate-700">
                {{ $member->name ?? $member->username }}
                @if($project->group->leader_id === $member->id)<span class="text-slate-500 font-medium">(Leader)</span>@endif
            </li>
        @endforeach
    </ul>
</section>
@endif

@if(session('success'))<div class="mb-4 p-3 rounded-lg bg-emerald-100 text-emerald-800 text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 p-3 rounded-lg bg-red-100 text-red-800 text-sm">{{ session('error') }}</div>@endif
@if(session('info'))<div class="mb-4 p-3 rounded-lg bg-blue-100 text-blue-800 text-sm">{{ session('info') }}</div>@endif

{{-- Step 1: Project completed = all 6 chapters + all supervisors approved. Only then can supervisors grade. --}}

@if($project->isFullyCompleted() && $project->group && $project->group->members->isNotEmpty())
    @if(!$project->allSupervisorsApproved())
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6 border-l-4 border-amber-400">
            <h2 class="font-semibold text-slate-900 mb-2">Scoring workflow</h2>
            <p class="text-sm text-slate-600 mb-4">All 6 chapters are completed. Grading is available only after <strong>all supervisors</strong> have approved the project via ProjectSupervisorApproval.</p>
            @if(!$currentUserHasApproved)
                <form action="{{ route('dashboard.docu-mentor.projects.approve', $project) }}" method="post" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-lg bg-amber-600 text-white text-sm font-medium hover:bg-amber-700">Approve project (I have reviewed all chapters)</button>
                </form>
            @else
                <p class="text-sm text-emerald-600 font-medium">You have approved. Waiting for other supervisor(s) to approve.</p>
            @endif
            <p class="text-xs text-slate-500 mt-3">Approvals: {{ $project->supervisorApprovals->whereNotNull('approved_at')->count() }} / {{ $project->supervisors->count() }}</p>
        </div>
    @endif
@endif

{{-- Step 2: Supervisor Dashboard → "Grade Students" – click opens list of all group members including leader. --}}
@if($canGrade && $project->group && $project->group->members->isNotEmpty())
    <div id="grade-students" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6 scroll-mt-4">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <div>
                <h2 class="font-semibold text-slate-900">Grade Students</h2>
                <p class="text-sm text-slate-600 mt-0.5">List of all group members (including leader). Document + System = 100 per student. Each supervisor grades independently. Final score = average of all supervisors’ scores.</p>
            </div>
        </div>
        {{-- Step 3: Supervisor assigns individual scores: Document Score, System Score, Remarks. Validation: document_score + system_score = 100. --}}
        <form action="{{ route('dashboard.docu-mentor.projects.scores.store', $project) }}" method="post">
            @csrf
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-600">Student</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-600">Document score</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-600">System score</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-600">Final (avg)</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-600">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @php $finalScoresByStudent = $project->getFinalScoresByStudent(); @endphp
                    @foreach($project->group->members as $member)
                        @php
                            $scoreRec = $project->studentScores->firstWhere(fn($x) => $x->student_id === $member->id && $x->supervisor_id === $user->id);
                            $isLeader = $project->group->leader_id === $member->id;
                            $finalScore = $finalScoresByStudent->get($member->id);
                        @endphp
                        <tr>
                            <td class="px-4 py-2 text-sm">{{ $member->name ?? $member->username }}@if($isLeader)<span class="ml-1 text-xs text-slate-500">(leader)</span>@endif</td>
                            <td class="px-4 py-2"><input type="number" name="doc_{{ $member->id }}" value="{{ $scoreRec?->document_score ?? '' }}" min="0" max="100" placeholder="0–100" class="w-20 rounded border-slate-300 text-sm" aria-label="Document score for {{ $member->name ?? $member->username }}"></td>
                            <td class="px-4 py-2"><input type="number" name="sys_{{ $member->id }}" value="{{ $scoreRec?->system_score ?? '' }}" min="0" max="100" placeholder="0–100" class="w-20 rounded border-slate-300 text-sm" aria-label="System score for {{ $member->name ?? $member->username }}"></td>
                            <td class="px-4 py-2 text-sm text-slate-600">{{ $finalScore !== null ? $finalScore . '/100' : '—' }}</td>
                            <td class="px-4 py-2"><input type="text" name="remarks_{{ $member->id }}" value="{{ $scoreRec?->remarks ?? '' }}" placeholder="Remarks" class="rounded border-slate-300 text-sm w-48 max-w-full"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="text-xs text-slate-500 mt-2">Validation: Document score + System score must equal 100 for each student. Final (avg) is the average of all supervisors’ total scores.</p>
            <button type="submit" class="mt-4 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Save scores</button>
        </form>
    </div>
@elseif($project->group && $project->group->members->isNotEmpty() && $project->isFullyCompleted())
    {{-- When not yet gradable: show "Grade Students" button that scrolls to approval section or explains next step --}}
    <p class="text-sm text-slate-600 mb-4">Grade Students will be available after all supervisors have approved the project.</p>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="md:col-span-2 space-y-6">
        @if($project->description)
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h2 class="font-semibold text-slate-900 mb-2">Description</h2>
                <p class="text-slate-600 whitespace-pre-wrap">{{ $project->description }}</p>
            </div>
        @endif

        {{-- Chapter submissions (wireframe: Chapter N – Submitted/Not Submitted – [View File] [Comment]) --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="font-semibold text-slate-900 mb-4">Chapter submissions</h2>
            @if($project->chapters->isEmpty())
                <p class="text-slate-500 text-sm">No chapters yet. A coordinator can add chapters.</p>
            @else
                <ul class="space-y-3">
                    @foreach($project->chapters as $ch)
                        @php
                            $hasSubmission = $ch->submissions->isNotEmpty();
                            $chapterUrl = route('dashboard.docu-mentor.chapters.show', [$project, $ch->order]);
                        @endphp
                        <li class="flex flex-wrap items-center justify-between gap-2 p-3 rounded-lg border border-slate-200 hover:border-slate-300 bg-slate-50/30">
                            <span class="font-medium text-slate-900">{{ $ch->title }}</span>
                            <span class="text-sm {{ $hasSubmission ? 'text-emerald-700' : 'text-slate-500' }}">
                                {{ $hasSubmission ? 'Submitted' : 'Not submitted' }}
                            </span>
                            <div class="flex items-center gap-2">
                                <a href="{{ $chapterUrl }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 no-underline">View</a>
                                <a href="{{ $chapterUrl }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 no-underline">Comment</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <p class="text-xs text-slate-500 mt-3">View and Comment open the chapter page where you can see files, add comments, and mark as reviewed.</p>
            @endif
        </div>

        {{-- Comments panel: your comments on this project's submissions --}}
        @php
            $projectComments = $project->chapters->flatMap(fn($ch) => $ch->submissions->flatMap(fn($s) => $s->comments->where('user_id', $user->id)));
        @endphp
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="font-semibold text-slate-900 mb-4">Comments</h2>
            @if($projectComments->isEmpty())
                <p class="text-slate-500 text-sm">No comments from you yet on this project. Use Comment on a chapter to add feedback.</p>
            @else
                <ul class="space-y-2">
                    @foreach($projectComments->take(20) as $comment)
                        <li class="text-sm text-slate-700 pl-3 border-l-2 border-slate-200">{{ $comment->comment_text }}</li>
                    @endforeach
                </ul>
                @if($projectComments->count() > 20)
                    <p class="text-xs text-slate-500 mt-2">Showing latest 20. View each chapter for full history.</p>
                @endif
            @endif
        </div>
    </div>

    <div class="space-y-6">
        {{-- Actions --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="font-semibold text-slate-900 mb-4">Actions</h2>
            <div class="space-y-2">
                <a href="{{ route('dashboard.docu-mentor.download-all', $project) }}" class="block w-full px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 text-center text-sm">Download All (ZIP)</a>
                <form action="{{ route('dashboard.docu-mentor.ai.summary', $project) }}" method="post">
                    @csrf
                    <button type="submit" class="block w-full px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 text-sm">Generate AI Summary</button>
                </form>
            </div>
        </div>

        {{-- Proposals --}}
        @if($project->proposals->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h2 class="font-semibold text-slate-900 mb-4">Proposals</h2>
                <ul class="space-y-2">
                    @foreach($project->proposals as $p)
                        <li class="flex flex-wrap items-center gap-2">
                            <span class="text-sm text-slate-700">v{{ $p->version_number }} — {{ $p->uploaded_at?->format('M j, Y') }}</span>
                            <a href="{{ route('dashboard.docu-mentor.proposals.download', [$project, $p]) }}" target="_blank" rel="noopener" class="text-indigo-600 hover:text-indigo-800 text-sm">Preview</a>
                            <a href="{{ route('dashboard.docu-mentor.proposals.download', [$project, $p]) }}?attachment=1" class="text-indigo-600 hover:text-indigo-800 text-sm">Download</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Previous project (when tagged): Leader & Supervisor can access parent's proposal and Chapter 6 submissions --}}
        @if(($canAccessParent ?? false) && $project->parentProject)
            @php $parent = $project->parentProject; @endphp
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 border-l-4 border-indigo-400">
                <h2 class="font-semibold text-slate-900 mb-2">Previous project (tagged)</h2>
                <p class="text-slate-500 text-sm mb-3">{{ $parent->title }} · {{ $parent->academicYear?->year ?? '—' }}</p>
                @if($parent->proposals->isNotEmpty())
                    <p class="text-xs font-medium text-slate-600 mb-1">Proposal</p>
                    <ul class="space-y-1 mb-4">
                        @foreach($parent->proposals as $p)
                            <li class="flex flex-wrap items-center gap-2">
                                <span class="text-sm text-slate-700">v{{ $p->version_number }} — {{ $p->uploaded_at?->format('M j, Y') }}</span>
                                <a href="{{ route('dashboard.docu-mentor.proposals.download', [$parent, $p]) }}" target="_blank" rel="noopener" class="text-indigo-600 hover:text-indigo-800 text-sm">Preview</a>
                                <a href="{{ route('dashboard.docu-mentor.proposals.download', [$parent, $p]) }}?attachment=1" class="text-indigo-600 hover:text-indigo-800 text-sm">Download</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
                @if($parent->chapters->isNotEmpty())
                    @php $ch6 = $parent->chapters->first(); @endphp
                    @if($ch6 && $ch6->submissions->isNotEmpty())
                        <p class="text-xs font-medium text-slate-600 mb-1">Chapter 6 submissions</p>
                        <ul class="space-y-1 text-sm text-slate-600">
                            @foreach($ch6->submissions as $s)
                                <li>{{ $s->uploaded_at?->format('M j, Y') }} — {{ basename($s->file ?? '') }}</li>
                            @endforeach
                        </ul>
                    @endif
                @endif
            </div>
        @endif

        {{-- Upload project files / final submission --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
            <h2 class="font-semibold text-slate-900 mb-4">Upload Files</h2>
            <form action="{{ route('dashboard.docu-mentor.files.upload', $project) }}" method="post" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <input type="file" name="brief_pdf" accept=".pdf" class="text-sm w-full">
                <input type="file" name="diary_pdf" accept=".pdf" class="text-sm w-full">
                <button type="submit" class="block w-full px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-sm">Upload Project Files</button>
            </form>
            <form action="{{ route('dashboard.docu-mentor.final-submission.upload', $project) }}" method="post" enctype="multipart/form-data" class="mt-4 pt-4 border-t border-slate-200">
                @csrf
                <input type="file" name="final_submission" accept=".pdf,.doc,.docx" required class="text-sm w-full mb-2">
                <button type="submit" class="block w-full px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 text-sm">Upload Final Submission</button>
            </form>
        </div>
    </div>
</div>
</div>
@endsection
