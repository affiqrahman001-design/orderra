<?php declare(strict_types=1);

namespace App\Contracts\Payments;

use App\Models\PaymentIntent;
use App\Models\PaymentProvider;

interface PaymentProviderAdapter
{
    public function driver(): string;

    public function supports(PaymentProvider $provider): bool;

    /**
     * Return normalized simulation result.
     *
     * @return array{
     *   outcome:string,
     *   intent_status:string,
     *   attempt_status:string,
     *   transaction_status:string,
     *   provider_reference:string,
     *   response_payload:array<string,mixed>
     * }
     */
    public function simulate(PaymentIntent $intent, array $payload = []): array;
}
