<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Validator;

trait RejectsSensitivePayloadFields
{
    protected function rejectSensitivePayloadFields(Validator $validator, array $payload, string $prefix = ''): void
    {
        foreach ($payload as $key => $value) {
            $keyString = (string) $key;
            $path = $prefix === '' ? $keyString : $prefix.'.'.$keyString;

            if ($this->isSensitivePayloadKey($keyString)) {
                $validator->errors()->add(
                    $path,
                    'Sensitive payment or secret fields are not accepted by ORDERra demo endpoints.'
                );

                continue;
            }

            if (is_array($value)) {
                $this->rejectSensitivePayloadFields($validator, $value, $path);
            }
        }
    }

    protected function isSensitivePayloadKey(string $key): bool
    {
        $normalized = strtolower(str_replace(['-', ' '], '_', $key));

        $blockedFragments = [
            'password',
            'passwd',
            'pwd',
            'secret',
            'token',
            'authorization',
            'api_key',
            'apikey',
            'private_key',
            'client_secret',
            'card_number',
            'cardnumber',
            'card_data',
            'payment_method_data',
            'pan',
            'cvv',
            'cvc',
            'security_code',
            'expiry',
            'exp_month',
            'exp_year',
            'account_number',
            'routing_number',
            'iban',
        ];

        foreach ($blockedFragments as $fragment) {
            if (str_contains($normalized, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
