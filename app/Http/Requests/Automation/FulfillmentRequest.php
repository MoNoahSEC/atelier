<?php

declare(strict_types=1);

namespace App\Http\Requests\Automation;

use Illuminate\Foundation\Http\FormRequest;

class FulfillmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tokenCan(config('automation.sanctum.write_ability')) ?? false;
    }

    public function rules(): array
    {
        return [
            // At least one of order_id or external_order_id must be present
            'order_id'          => ['required_without:external_order_id', 'nullable', 'integer', 'min:1'],
            'external_order_id' => ['required_without:order_id', 'nullable', 'string', 'max:255'],

            'fulfillment_status' => ['required', 'string', 'in:unfulfilled,partially_fulfilled,fulfilled,cancelled'],
            'shipping_status'    => ['sometimes', 'nullable', 'string', 'in:pending,processing,shipped,delivered,returned,cancelled'],
            'tracking_number'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'carrier'            => ['sometimes', 'nullable', 'string', 'max:64'],

            // Idempotency key to prevent duplicate processing
            'idempotency_key'   => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required_without'          => 'Either order_id or external_order_id is required.',
            'external_order_id.required_without'  => 'Either order_id or external_order_id is required.',
            'idempotency_key.required'            => 'An idempotency_key is required to prevent duplicate fulfillment.',
        ];
    }
}
