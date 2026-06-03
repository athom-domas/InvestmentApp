<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWatchlistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('watchlist'));
    }

    public function rules(): array
    {
        return [
            'security_id' => ['required', 'integer', 'exists:securities,id'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ];
    }
}
