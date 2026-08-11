<?php

namespace App\Services;

use App\Models\AutomationRun;
use App\Models\Lead;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class N8nWorkflowService
{
    public function triggerLeadEnrichment(Lead $lead, AutomationRun $run): void
    {
        $run->update(['status' => 'running', 'started_at' => now()]);

        try {
            $payload = [
                'event' => 'lead.created',
                'lead' => [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'email' => $lead->email,
                    'company' => $lead->company,
                    'website' => $lead->website,
                ],
                'callback' => [
                    'url' => url('/api/v1/n8n/callbacks/lead-enrichment'),
                    'secret' => config('services.n8n.callback_secret'),
                ],
                'idempotency_key' => (string) $run->id,
            ];

            $response = Http::timeout(config('services.n8n.timeout'))
                ->retry(2, 200)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-NexusOps-Secret' => config('services.n8n.callback_secret'),
                    'X-NexusOps-Run-Id' => (string) $run->id,
                ])
                ->post(config('services.n8n.webhook_url'), $payload);

            if ($response->failed()) {
                throw new \RuntimeException('n8n returned HTTP ' . $response->status());
            }

            $run->update([
                'status' => 'accepted',
                'execution_id' => $response->json('execution_id') ?? $response->json('run_id') ?? Str::uuid()->toString(),
                'output' => $response->json(),
                'finished_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'finished_at' => now(),
            ]);
            throw $exception;
        }
    }
}
