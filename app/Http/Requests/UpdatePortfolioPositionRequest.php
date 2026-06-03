<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePortfolioPositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('portfolio'));
    }

    public function rules(): array
    {
        return [
            'quantity'      => ['sometimes', 'numeric', 'min:0.000001'],
            'average_price' => ['nullable', 'numeric', 'min:0'],
            'currency'      => ['nullable', 'string', 'size:3'],
            'opened_at'     => ['nullable', 'date'],
            'notes'         => ['nullable', 'string', 'max:500'],
        ];
    }
}
