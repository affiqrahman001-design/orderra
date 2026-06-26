<?php

declare(strict_types=1);

namespace App\Services\Ops;

use App\Models\OpsWebhookEvent;
use Illuminate\Validation\ValidationException;

final class OpsWebhookReplayService
{
    public function replay(OpsWebhookEvent $event, ?string $note = null): OpsWebhookEvent
    {
        if ((bool) config('ops.webhooks.replay.enabled', false) !== true) {
            throw ValidationException::withMessages([
                'replay' => 'Replay untuk ops webhook tidak dibenarkan.',
            ]);
        }

        $maxAttempts = (int) config('ops.webhooks.replay.max_attempts', 5);

        if (((int) $event->replay_count + 1) > $maxAttempts) {
            throw ValidationException::withMessages([
                'replay' => sprintf('Replay melebihi had maksimum [%d].', $maxAttempts),
            ]);
        }

        $event->update([
            'status' => 'replayed',
            'last_replayed_at' => now(),
            'replay_count' => (int) $event->replay_count + 1,
            'notes' => $this->mergeNotes($event->notes, $note),
            'failed_at' => null,
            'error_message' => null,
        ]);

        return $event->fresh([
            'order',
            'refund',
            'paymentIntent',
            'deliveryAssignment',
        ]);
    }

    protected function mergeNotes(?string $existing, ?string $incoming): ?string
    {
        $existing = trim((string) ($existing ?? ''));
        $incoming = trim((string) ($incoming ?? ''));

        if ($existing === '') {
            return $incoming !== '' ? $incoming : null;
        }

        if ($incoming === '') {
            return $existing;
        }

        return $existing.PHP_EOL.PHP_EOL.'[Replay] '.$incoming;
    }
}
