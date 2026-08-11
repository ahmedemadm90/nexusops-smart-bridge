<?php

namespace Tests\Feature;

use App\Models\AutomationRun;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NexusOpsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_operator_can_create_a_lead_and_trigger_n8n(): void
    {
        Http::fake([
            '*' => Http::response(['accepted' => true, 'execution_id' => 'exec-123'], 202),
        ]);
        $operator = User::factory()->create();

        $response = $this->actingAs($operator)->postJson('/api/v1/leads', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@analytical.test',
            'company' => 'Analytical Engines',
            'website' => 'https://analytical.test',
        ]);

        $response->assertStatus(202)->assertJsonPath('automation_run.status', 'accepted');
        $this->assertDatabaseHas('leads', ['email' => 'ada@analytical.test', 'status' => 'enriching']);
        $this->assertDatabaseHas('automation_runs', ['execution_id' => 'exec-123', 'status' => 'accepted']);
        Http::assertSent(fn ($request) => $request->hasHeader('X-NexusOps-Secret') && $request['lead']['email'] === 'ada@analytical.test');
    }

    public function test_signed_callback_updates_lead_and_run_once(): void
    {
        $lead = Lead::create(['name' => 'Lead', 'email' => 'lead@example.com', 'status' => 'enriching']);
        $run = $lead->automationRuns()->create(['workflow_name' => 'lead-enrichment-v1', 'status' => 'running']);
        $payload = [
            'run_id' => $run->id,
            'lead_id' => $lead->id,
            'score' => 90,
            'status' => 'completed',
            'execution_id' => 'exec-456',
            'enrichment' => ['qualification' => 'hot'],
        ];

        $this->withHeader('X-NexusOps-Secret', 'change-me-in-production')
            ->postJson('/api/v1/n8n/callbacks/lead-enrichment', $payload)
            ->assertOk()
            ->assertJsonPath('lead.status', 'enriched')
            ->assertJsonPath('lead.score', 90);

        $this->withHeader('X-NexusOps-Secret', 'wrong')
            ->postJson('/api/v1/n8n/callbacks/lead-enrichment', $payload)
            ->assertUnauthorized();

        $this->assertSame('completed', $run->fresh()->status);
        $this->assertSame('enriched', $lead->fresh()->status);
    }
}
