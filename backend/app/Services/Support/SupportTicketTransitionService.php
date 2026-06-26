<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Models\SupportTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SupportTicketTransitionService
{
    public function transition(
        SupportTicket $ticket,
        string $toStatus,
        ?string $note = null,
        ?string $resolutionSummary = null,
        array $payload = [],
        string $actorType = 'admin',
        ?int $actorId = null,
    ): SupportTicket {
        $allowed = (array) config("support.transitions.{$ticket->status}", []);

        if (! in_array($toStatus, $allowed, true)) {
            throw ValidationException::withMessages([
                'to_status' => sprintf(
                    'Transition dari [%s] ke [%s] tidak dibenarkan.',
                    $ticket->status,
                    $toStatus
                ),
            ]);
        }

        return DB::transaction(function () use ($ticket, $toStatus, $note, $resolutionSummary, $payload, $actorType, $actorId): SupportTicket {
            $fromStatus = $ticket->status;

            $update = [
                'status' => $toStatus,
            ];

            if ($resolutionSummary !== null && trim($resolutionSummary) !== '') {
                $update['resolution_summary'] = $resolutionSummary;
            }

            if ($toStatus === 'resolved') {
                $update['resolved_at'] = now();
            }

            if ($toStatus === 'closed') {
                $update['closed_at'] = now();
            }

            $ticket->update($update);

            $ticket->events()->create([
                'event_name' => 'ticket_status_changed',
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'note' => $note,
                'payload' => $payload,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'occurred_at' => now(),
            ]);

            return $ticket->fresh([
                'order',
                'refund',
                'paymentIntent',
                'events',
            ]);
        });
    }
}
