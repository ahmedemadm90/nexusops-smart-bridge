<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'operator@nexusops.test'],
            ['name' => 'NexusOps Operator', 'password' => Hash::make('password')]
        );

        Lead::updateOrCreate(
            ['email' => 'demo@acme.test'],
            [
                'name' => 'Demo Buyer',
                'company' => 'Acme Labs',
                'website' => 'https://acme.test',
                'status' => 'enriched',
                'score' => 85,
                'enrichment' => ['qualification' => 'hot', 'ruleset' => 'lead-enrichment-v1'],
                'enriched_at' => now(),
            ]
        );
    }
}
