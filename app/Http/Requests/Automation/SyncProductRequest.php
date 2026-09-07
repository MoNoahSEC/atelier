<?php

declare(strict_types=1);

namespace App\Http\Requests\Automation;

use Illuminate\Foundation\Http\FormRequest;

class SyncProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tokenCan(config('automation.sanctum.write_ability')) ?? false;
    }

    public function rules(): array
    {
        return [
            'sku'                 => ['required', 'string', 'max:128'],
            'title'               => ['required', 'string', 'max:512'],
            'slug'                => ['sometimes', 'string', 'max:512', 'regex:/^[a-z0-9\-]+$/'],
            'description'         => ['sometimes', 'nullable', 'string', 'max:65535'],
            'cost_price_minor'    => ['required', 'integer', 'min:0'],
            'retail_price_minor'  => ['required', 'integer', 'min:0'],
            'currency'            => ['required', 'string', 'size:3'],
            'inventory'           => ['sometimes', 'integer', 'min:0'],
            'attributes'          => ['sometimes', 'nullable', 'array'],
            'status'              => ['sometimes', 'string', 'in:active,inactive,draft,archived'],
            'supplier_code'       => ['sometimes', 'nullable', 'string', 'max:64'],
            'external_id'         => ['sometimes', 'nullable', 'string', 'max:255'],

            // Optional variants array
            'variants'                          => ['sometimes', 'array'],
            'variants.*.sku'                    => ['required_with:variants', 'string', 'max:128'],
            'variants.*.title'                  => ['required_with:variants', 'string', 'max:512'],
            'variants.*.cost_price_minor'       => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.retail_price_minor'     => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.inventory'              => ['sometimes', 'integer', 'min:0'],
            'variants.*.attributes'             => ['sometimes', 'nullable', 'array'],
            'variants.*.status'                 => ['sometimes', 'string', 'in:active,inactive,draft,archived'],
        ];
    }

    public function messages(): array
    {
        return [
            'cost_price_minor.integer'   => 'cost_price_minor must be an integer (minor currency units).',
            'retail_price_minor.integer' => 'retail_price_minor must be an integer (minor currency units).',
            'currency.size'              => 'currency must be a 3-character ISO 4217 code.',
        ];
    }
}
