<?php

namespace App\Http\Requests\Admin\General;

use Illuminate\Foundation\Http\FormRequest;

class SliderRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id'=>'nullable|exists:categories,id',
            'title.en' => 'required',
            'title.ar' => 'required',
            'sub_title.en' => 'required',
            'sub_title.ar' => 'required',
            'image' => 'required|image|mimes:png,jpg,jpeg',
            ];
    }
}
