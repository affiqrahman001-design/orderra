<?php

declare(strict_types=1);

namespace App\Http\Requests\Ops;

use App\Http\Requests\ApiRequest;
use App\Http\Requests\Concerns\RejectsSensitivePayloadFields;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreOpsWebhookSimulationRequest extends ApiRequest
{
    use RejectsSensitivePayloadFields;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'event_name' => $this->filled('event_name')
              ? Str::lower((string) $this->input('event_name'))
              : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'event_name' => [
                'required',
                'string',
                Rule::in((array) config('ops.webhooks.allowed_events', [])),
            ],
            'order_id' => ['nullable', 'string', 'max:100'],
            'refund_id' => ['nullable', 'string', 'max:100'],
            'payment_intent_id' => ['nullable', 'string', 'max:100'],
            'delivery_assignment_id' => ['nullable', 'string', 'max:100'],
            'payload' => ['nullable', 'array', 'max:50'],
            'headers' => ['nullable', 'array', 'max:20'],
            'headers.*' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $eventName = (string) $this->input('event_name');

            if ($eventName === 'payment.updated' && ! $this->filled('payment_intent_id')) {
                $validator->errors()->add('payment_intent_id', 'payment.updated memerlukan payment_intent_id.');
            }

            if ($eventName === 'refund.updated' && ! $this->filled('refund_id')) {
                $validator->errors()->add('refund_id', 'refund.updated memerlukan refund_id.');
            }

            if (
                in_array($eventName, ['rider.assigned', 'rider.location_updated'], true)
                && ! $this->filled('delivery_assignment_id')
            ) {
                $validator->errors()->add('delivery_assignment_id', sprintf('%s memerlukan delivery_assignment_id.', $eventName));
            }

            if (
                $eventName === 'order.delivered'
                && ! $this->filled('order_id')
                && ! $this->filled('delivery_assignment_id')
            ) {
                $validator->errors()->add('order_id', 'order.delivered memerlukan order_id atau delivery_assignment_id.');
            }

            $this->rejectSensitivePayloadFields($validator, $this->all());
        });
    }
}
