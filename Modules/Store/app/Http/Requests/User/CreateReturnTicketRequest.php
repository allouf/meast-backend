<?php

namespace Modules\Store\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class CreateReturnTicketRequest extends FormRequest
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
            'order_id' => 'required|exists:store_orders,id',

            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.quantity' => 'required|integer|min:1',

            'reason' => 'required|string|max:500',
            'description' => 'nullable|string',

            'preferred_solution' => 'required|in:refund,replacement',

            'attachments.*' => 'file|mimes:jpg,jpeg,png,mp4,mov,avi|max:20480',
        ];
    }
}
