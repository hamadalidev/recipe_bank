<?php

namespace App\Http\Requests\Recipe;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecipeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $recipe = $this->route('recipe');
        return $this->user()->can('update', $recipe);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'ingredients' => ['sometimes', 'required', 'array', 'min:1'],
            'ingredients.*' => ['required', 'string', 'max:255'],
            'steps' => ['sometimes', 'required', 'array', 'min:1'],
            'steps.*' => ['required', 'string'],
            'cuisine_type_id' => ['sometimes', 'required', 'exists:cuisine_types,id'],
            'image' => ['nullable', 'file', 'image', 'max:2048'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Recipe name is required.',
            'description.required' => 'Recipe description is required.',
            'ingredients.required' => 'At least one ingredient is required.',
            'ingredients.min' => 'At least one ingredient is required.',
            'ingredients.*.required' => 'Ingredient cannot be empty.',
            'steps.required' => 'At least one step is required.',
            'steps.min' => 'At least one step is required.',
            'steps.*.required' => 'Step cannot be empty.',
            'cuisine_type_id.required' => 'Please select a cuisine type.',
            'cuisine_type_id.exists' => 'Selected cuisine type is invalid.',
        ];
    }
}
