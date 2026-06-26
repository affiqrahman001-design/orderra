<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\TransitionSupportTicketRequest;
use App\Http\Resources\SupportTicketResource;
use App\Models\SupportTicket;
use App\Services\Admin\AdminAuditLogger;
use App\Services\Admin\AdminNotificationLogger;
use App\Services\Support\SupportTicketTransitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SupportTicketAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SupportTicket::query()
            ->with(['order', 'refund', 'paymentIntent']);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }

        if ($search = trim($request->string('q')->toString())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('public_id', 'like', '%'.$search.'%')
                    ->orWhere('subject', 'like', '%'.$search.'%')
                    ->orWhereHas('order', function ($orderQuery) use ($search): void {
                        $orderQuery
                            ->where('public_id', 'like', '%'.$search.'%')
                            ->orWhere('order_code', 'like', '%'.$search.'%');
                    });
            });
        }

        $paginator = $query
            ->latest('id')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return response()->json([
            'data' => $paginator->getCollection()->map(
                fn (SupportTicket $ticket) => $this->mapSummary($ticket)
            )->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(SupportTicket $supportTicket): SupportTicketResource
    {
        return new SupportTicketResource(
            $supportTicket->load([
                'order',
                'refund',
                'paymentIntent',
                'events',
            ])
        );
    }

    public function transition(
        TransitionSupportTicketRequest $request,
        SupportTicket $supportTicket,
        SupportTicketTransitionService $supportTicketTransitionService,
    ): JsonResponse {
        $toStatus = (string) $request->validated('to_status');
        $note = $request->validated('note');
        $resolutionSummary = $request->validated('resolution_summary');

        $ticket = $supportTicketTransitionService->transition(
            ticket: $supportTicket,
            toStatus: $toStatus,
            note: $note,
            resolutionSummary: $resolutionSummary,
            payload: $request->validated('payload', []),
            actorType: 'admin',
            actorId: null,
        );

        app(AdminAuditLogger::class)->logAdminAction(
            action: 'support_ticket.transition',
            entityType: 'support_ticket',
            entityPublicId: $ticket->public_id,
            entitySecondaryKey: $ticket->subject,
            summary: sprintf('Support ticket moved to %s.', $ticket->status),
            requestSnapshot: [
                'to_status' => $toStatus,
                'note' => $note,
                'resolution_summary' => $resolutionSummary,
            ],
            contextSnapshot: [
                'category' => $ticket->category,
                'status' => $ticket->status,
            ],
        );

        app(AdminNotificationLogger::class)->logSimulated(
            channel: 'in_app',
            notificationType: 'support_ticket_status_changed',
            recipientType: 'customer',
            recipientKey: data_get($ticket->contact_snapshot, 'email')
            ?: data_get($ticket->contact_snapshot, 'phone')
            ?: data_get($ticket->contact_snapshot, 'name')
            ?: data_get($ticket->links, 'order.order_code'),
            entityType: 'support_ticket',
            entityPublicId: $ticket->public_id,
            subject: 'Support ticket updated',
            title: 'Support '.$ticket->public_id,
            bodyPreview: sprintf('Status support ticket telah dikemas kini ke %s.', $ticket->status),
            meta: [
                'category' => $ticket->category,
                'status' => $ticket->status,
            ],
        );

        $resource = new SupportTicketResource($ticket);
        $resolved = $resource->resolve();

        return response()->json([
            'message' => 'Support ticket transitioned successfully.',
            'data' => $resolved['data'],
        ]);
    }

    private function mapSummary(SupportTicket $ticket): array
    {
        return [
            'id' => $ticket->public_id,
            'category' => $ticket->category,
            'status' => $ticket->status,
            'subject' => $ticket->subject,
            'order' => $ticket->order ? [
                'id' => $ticket->order->public_id,
                'order_code' => $ticket->order->order_code,
                'status' => $ticket->order->status,
            ] : null,
            'refund' => $ticket->refund ? [
                'id' => $ticket->refund->public_id,
                'status' => $ticket->refund->status,
            ] : null,
            'payment_intent_id' => $ticket->paymentIntent?->public_id,
            'opened_at' => optional($ticket->opened_at)?->toIso8601String(),
            'resolved_at' => optional($ticket->resolved_at)?->toIso8601String(),
            'closed_at' => optional($ticket->closed_at)?->toIso8601String(),
        ];
    }

    private function perPage(Request $request): int
    {
        $default = (int) config('admin.pagination.default_per_page', 15);
        $max = (int) config('admin.pagination.max_per_page', 50);
        $requested = max(1, (int) $request->integer('per_page', $default));

        return min($requested, $max);
    }
}
