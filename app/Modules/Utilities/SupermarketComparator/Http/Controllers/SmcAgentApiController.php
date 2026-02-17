<?php

namespace App\Modules\Utilities\SupermarketComparator\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SmcAgentJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SmcAgentApiController extends Controller
{
    public function nextJob(Request $request)
    {
        if (!$this->authorized($request)) {
            return response()->json(['message' => 'Unauthorized agent token.'], 401);
        }

        $validated = $request->validate([
            'device_id' => 'required|string|max:120',
        ]);

        $deviceId = trim((string) $validated['device_id']);

        $job = DB::transaction(function () use ($deviceId) {
            $candidate = SmcAgentJob::where('device_id', $deviceId)
                ->where('status', 'pending')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (!$candidate) {
                return null;
            }

            $candidate->status = 'in_progress';
            $candidate->started_at = now();
            $candidate->locked_at = now();
            $candidate->save();

            return $candidate;
        });

        if (!$job) {
            return response()->json(['job' => null]);
        }

        return response()->json([
            'job' => [
                'id' => $job->id,
                'uuid' => $job->uuid,
                'store' => $job->store,
                'items' => $job->items,
            ],
        ]);
    }

    public function updateStatus(Request $request, int $jobId)
    {
        if (!$this->authorized($request)) {
            return response()->json(['message' => 'Unauthorized agent token.'], 401);
        }

        $validated = $request->validate([
            'device_id' => 'required|string|max:120',
            'stage' => 'required|string|in:started,progress,completed,failed',
            'current' => 'nullable|integer|min:0|max:100000',
            'total' => 'nullable|integer|min:0|max:100000',
            'title' => 'nullable|string|max:255',
            'added' => 'nullable|array',
            'failed' => 'nullable|array',
            'error' => 'nullable|string|max:2000',
        ]);

        $job = SmcAgentJob::where('id', $jobId)
            ->where('device_id', $validated['device_id'])
            ->first();

        if (!$job) {
            return response()->json(['message' => 'Job not found for this device.'], 404);
        }

        $stage = $validated['stage'];

        if ($stage === 'started') {
            $job->status = 'in_progress';
            $job->started_at = $job->started_at ?: now();
            $job->locked_at = now();
            $job->save();
            return response()->json(['ok' => true]);
        }

        if ($stage === 'progress') {
            $job->status = 'in_progress';
            $job->locked_at = now();
            $job->progress = [
                'current' => isset($validated['current']) ? (int) $validated['current'] : null,
                'total' => isset($validated['total']) ? (int) $validated['total'] : null,
                'title' => isset($validated['title']) ? (string) $validated['title'] : null,
                'updated_at' => now()->toIso8601String(),
            ];
            $job->save();
            return response()->json(['ok' => true]);
        }

        if ($stage === 'completed') {
            $job->status = 'completed';
            $job->completed_at = now();
            $job->result = [
                'added' => $validated['added'] ?? [],
                'failed' => $validated['failed'] ?? [],
            ];
            $job->error_message = null;
            $job->locked_at = now();
            $job->save();
            return response()->json(['ok' => true]);
        }

        $job->status = 'failed';
        $job->failed_at = now();
        $job->error_message = (string) ($validated['error'] ?? 'Error desconocido');
        $job->locked_at = now();
        $job->save();

        return response()->json(['ok' => true]);
    }

    private function authorized(Request $request): bool
    {
        $expected = (string) config('services.supermarket_comparator.agent_api_token', '');
        if ($expected === '') {
            return false;
        }

        $raw = (string) $request->header('Authorization', '');
        $token = preg_replace('/^Bearer\s+/i', '', $raw) ?: '';

        return hash_equals($expected, trim($token));
    }
}
