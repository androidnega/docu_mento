<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscribeController extends Controller
{
    /**
     * Store or update a student's push subscription for exam reminders.
     * Student must be logged in (dashboard.auth).
     */
    public function store(Request $request): JsonResponse
    {
        $student = $this->student();
        if (! $student) {
            abort(401, 'Not authenticated as student.');
        }
        $request->validate([
            'endpoint' => 'required|string|max:500',
            'keys' => 'required|array',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $endpoint = $request->input('endpoint');
        $publicKey = $request->input('keys.p256dh');
        $authToken = $request->input('keys.auth');

        PushSubscription::updateOrCreate(
            [
                'student_id' => $student->id,
                'endpoint' => $endpoint,
            ],
            [
                'public_key' => $publicKey,
                'auth_token' => $authToken,
            ]
        );

        return response()->json(['success' => true]);
    }

    protected function student(): ?Student
    {
        $user = auth()->user();
        if ($user instanceof Student) {
            return $user;
        }
        if ($user && method_exists($user, 'index_number')) {
            return Student::where('index_number_hash', Student::hashIndexNumber($user->index_number ?? ''))->first();
        }
        return null;
    }
}
