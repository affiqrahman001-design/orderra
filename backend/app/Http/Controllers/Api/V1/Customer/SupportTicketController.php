<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\StoreSupportTicketRequest;
use App\Http\Resources\SupportTicketResource;
use App\Models\SupportTicket;
use App\Services\Support\CreateSupportTicketService;
use Illuminate\Http\JsonResponse;

final class SupportTicketController extends Controller
{
    public function store(
        StoreSupportTicketRequest $request,
        CreateSupportTicketService $createSupportTicketService,
    ): JsonResponse {
        $ticket = $createSupportTicketService->handle(
            payload: $request->validated(),
            actorType: 'customer',
            actorId: null,
        );

        $resource = new SupportTicketResource($ticket);
        $resolved = $resource->resolve();

        return response()->json([
            'message' => 'Support ticket created successfully.',
            'data' => $resolved['data'],
        ], 201);
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
}
