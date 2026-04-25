<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'type' => 'required|in:simple,rating,satisfaction',
            'privacy' => 'required|in:private,public',
            'blog' => 'nullable|boolean',
            'forum' => 'nullable|boolean',
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date|after_or_equal:startDate',
            'users' => 'nullable|array',
            'users.*' => 'nullable|distinct',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'The survey type is required.',
            'type.in' => 'The selected survey type is invalid.',
            'title.required' => 'The title is required.',
            'title.max' => 'The title must not exceed 255 characters.',
            'privacy.required' => 'The privacy field is required.',
            'privacy.in' => 'The selected privacy is invalid.',
            'endDate.after_or_equal' => 'The end date must be after or equal to the start date.',
            'users.*.distinct' => 'Each user must be unique.',
        ];
    }
}