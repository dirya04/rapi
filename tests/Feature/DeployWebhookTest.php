<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DeployWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_deploy_webhook_rejects_invalid_secret_with_403(): void
    {
        Config::set('app.deploy_secret', 'secret_token_correct');

        $response = $this->getJson('/api/deploy-migrate-wrong_secret_token');

        $response->assertStatus(403)
            ->assertJson([
                'status'  => 'error',
                'message' => 'Forbidden: Invalid deployment secret token.',
            ]);
    }

    public function test_deploy_webhook_succeeds_with_valid_secret(): void
    {
        Config::set('app.deploy_secret', 'secret_token_correct');

        $response = $this->getJson('/api/deploy-migrate-secret_token_correct');

        $response->assertOk()
            ->assertJson([
                'status'  => 'success',
                'message' => 'Database migration and deployment tasks executed successfully.',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'details' => [
                    'migrate',
                    'storage_link',
                    'optimize_clear',
                ],
            ]);
    }
}

