<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TourParamRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'dateFrom' => 'date',
            'dateTo' => 'date',
            'priceFrom' => 'numeric',
            'priceTo' => 'numeric',
            'sortBy' => Rule::in(['price']),
            'sortOrder' => Rule::in(['asc', 'desc']),
        ];
    }

    public function messages(): array
    {
        return [
            'sortBy' => "The sortBy parameter accepts only 'price' value",
            'sortOrder' => "The sortOrder parameter accepts only 'asc' or 'desc' values",
        ];
    }
}
