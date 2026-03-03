@props(['status' => 'draft', 'label' => null])
@php
    $status = strtolower((string) $status);
    $labels = [
        'approved' => 'Approved',
        'pending' => 'Pending',
        'rejected' => 'Rejected',
        'draft' => 'Draft',
        'submitted' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'graded' => 'Graded',
        'archived' => 'Archived',
    ];
    $label = $label ?? $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    $classes = match($status) {
        'approved', 'completed', 'graded' => 'bg-green-100 text-green-800',
        'pending', 'submitted', 'in_progress' => 'bg-amber-100 text-amber-800',
        'rejected' => 'bg-red-100 text-red-800',
        'draft', 'archived' => 'bg-blue-100 text-blue-800',
        default => 'bg-slate-100 text-slate-700',
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium $classes"]) }}>{{ $label ?? ($labels[$status] ?? ucfirst(str_replace('_', ' ', $status))) }}</span>
