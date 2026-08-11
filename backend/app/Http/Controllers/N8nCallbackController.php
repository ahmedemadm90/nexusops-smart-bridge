<?php

namespace App\Http\Controllers;

use App\Models\AutomationRun;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class N8nCallbackController extends Controller
{
    public function leadEnrichment(Request $request): JsonResponse
    {
        abort_unless(hash_equals(
            (string) config('services.n8n.callback_secret'),
            (string) $request->header('X-NexusOps-Secret')
        ), 401, 'Invalid callback signature.');

        $data = $request->validate([
            'run_id' => ['required', 'integer', 'exists:automation_runs,id'],
            'lead_id' => ['required', 'integer', 'exists:leads,id'],
            'score' => ['required', 'integer', 'between:0,100'],
            'status' => ['required', 'in:completed,failed'],
            'enrichment' => ['nullable', 'array'],
            'execution_id' => ['nullable', 'string', 'max:255'],
            'verified_at' => ['nullable', 'date'],
        ]);

        $result = DB::transaction(function () use ($data) {
            $run = AutomationRun::lockForUpdate()->findOrFail($data['run_id']);
            if ($run->status === 'completed') {
                return [$run->lead, $run];
            }

            $lead = Lead::lockForUpdate()->findOrFail($data['lead_id']);
            $lead->update([
                'status' => $data['status'] === 'completed' ? 'enriched' : 'enrichment_failed',
                'score' => $data['score'],
                'enrichment' => $data['enrichment'] ?? [],
                'enriched_at' => $data['verified_at'] ?? now(),
            ]);
            $run->update([
                'status' => $data['status'],
                'execution_id' => $data['execution_id'] ?? $run->execution_id,
                'output' => $data,
                'finished_at' => now(),
            ]);
            return [$lead, $run];
        });

        return response()->json(['lead' => $result[0]->fresh(), 'automation_run' => $result[1]->fresh()]);
    }
}
