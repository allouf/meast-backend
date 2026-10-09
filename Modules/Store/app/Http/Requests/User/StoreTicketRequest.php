<?php

namespace Modules\Store\Http\Requests\User;

use Modules\Store\Models\IssueType;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
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
            'order_item_ids'   => 'required|array|min:1',
            'order_item_ids.*' => 'integer|exists:order_items,id',
            'issue_type_id'  => 'required|exists:issue_types,id',
            'subject'  => 'required|string|max:255',
            'message'  => 'required|string',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,mp4,mov,avi|max:20480',

            'items' => 'array|min:1',
            'items.*.order_item_id' => 'exists:order_items,id',
            'items.*.quantity' => 'integer|min:1',

            'reason' => 'string|max:500',
            'description' => 'nullable|string',
            'preferred_solution' => 'in:refund,replacement',
        ];
    }

    public function withValidator($validator)
    {
        $validator->sometimes([
            'items',
            'items.*.order_item_id',
            'items.*.quantity',
            'reason',
            'preferred_solution',
        ], 'required', function () {
            $issueType = IssueType::find($this->input('issue_type_id'));
            return $issueType?->name === 'RETURN';
        });
    }
}


