<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'max:500'],
            'country' => ['nullable', 'string', 'max:100'],
            'port' => ['nullable', 'integer', 'between:1,65535'],
            'org' => ['nullable', 'string', 'max:150'],
            'product' => ['nullable', 'string', 'max:150'],
            'os' => ['nullable', 'string', 'max:150'],
        ];
    }

    /**
     * The free-text query plus every filled filter field, composed into a
     * single Shodan query string (e.g. `apache country:"France" port:22`).
     */
    public function composedQuery(): string
    {
        $parts = [$this->validated('query')];

        foreach (['country' => 'country', 'org' => 'org', 'product' => 'product', 'os' => 'os'] as $field => $shodanFilter) {
            if ($this->filled($field)) {
                $parts[] = sprintf('%s:"%s"', $shodanFilter, str_replace('"', '', $this->validated($field)));
            }
        }

        if ($this->filled('port')) {
            $parts[] = 'port:'.(int) $this->validated('port');
        }

        return implode(' ', $parts);
    }
}
