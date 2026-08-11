<?php

namespace App\Http\Controllers;

use App\Models\AutomationRun;
use App\Models\Lead;
use App\Services\N8nWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class LeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $leads = Lead::query()
            ->when($request->string('status')->trim()->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->string('search')->trim()->value(), function ($query, string $search) {
                $query->where(fn ($nested) => $nested->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('company', 'like', "%{$search}%"));
            })
            ->withCount('automationRuns')
            ->latest()
            ->paginate(min($request->integer('per_page', 20), 100));

        return response()->json($leads);
    }

    public function store(Request $request, N8nWorkflowService $workflowService): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:160'],
            'website' => ['nullable', 'url', 'max:255'],
        ]);

        $lead = Lead::create($data + ['status' => 'enriching']);
        $run = $lead->automationRuns()->create([
            'workflow_name' => 'lead-enrichment-v1',
            'status' => 'queued',
            'input' => $data,
        ]);

        try {
            $workflowService->triggerLeadEnrichment($lead, $run);
        } catch (Throwable) {
            // The failed state is stored on the run. Clients can inspect and retry it.
        }

        return response()->json([
            'lead' => $lead->fresh(),
            'automation_run' => $run->fresh(),
        ], 202);
    }

    public function show(Lead $lead): JsonResponse
    {
        return response()->json($lead->load('automationRuns'));
    }

    public function retry(Lead $lead, N8nWorkflowService $workflowService): JsonResponse
    {
        $run = $lead->automationRuns()->create([
            'workflow_name' => 'lead-enrichment-v1',
            'status' => 'queued',
            'input' => $lead->only(['name', 'email', 'company', 'website']),
        ]);
        $lead->update(['status' => 'enriching']);

        try {
            $workflowService->triggerLeadEnrichment($lead, $run);
        } catch (Throwable) {
            // The run contains the failure detail for observability.
        }

        return response()->json(['lead' => $lead->fresh(), 'automation_run' => $run->fresh()], 202);
    }
}
