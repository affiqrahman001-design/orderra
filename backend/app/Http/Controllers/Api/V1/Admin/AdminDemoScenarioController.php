<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class AdminDemoScenarioController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'guards' => [
                    'payments_demo_mode' => (bool) config('payments.demo_mode', false),
                    'payments_block_live_execution' => (bool) config('payments.block_live_execution', true),
                    'payments_allow_webhook_simulation' => (bool) config('payments.allow_webhook_simulation', false),
                    'ops_replay_enabled' => (bool) config('ops.webhooks.replay.enabled', false),
                ],
                'simulation_rules' => [
                    'payment_outcomes' => (array) config('payments.simulation.allowed_outcomes', []),
                    'payment_webhook_events' => (array) config('payments.webhook_simulation.allowed_events', []),
                    'refund_hook_types' => (array) config('payments.refund_hook_simulation.allowed_types', []),
                    'rider_flow' => (array) config('riders.simulation.flow', []),
                    'ops_webhook_events' => (array) config('ops.webhooks.allowed_events', []),
                ],
                'scenarios' => (array) config('admin.demo_scenarios', []),
            ],
        ]);
    }
}
