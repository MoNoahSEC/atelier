<?php

declare(strict_types=1);

namespace App\Http\Requests\Automation;

use Illuminate\Foundation\Http\FormRequest;

class PendingOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tokenCan(config('automation.sanctum.read_ability')) ?? false;
    }

    public function rules(): array
    {
        $maxPerPage = config('automation.pagination.max_per_page', 500);

        return [
            'cursor'        => ['sometimes', 'nullable', 'string'],
            'per_page'      => ['sometimes', 'integer', 'min:1', "max:{$maxPerPage}"],
            'currency'      => ['sometimes', 'string', 'size:3'],
            'created_after' => ['sometimes', 'date_format:Y-m-d\TH:i:s\Z'],
        ];
    }
}
