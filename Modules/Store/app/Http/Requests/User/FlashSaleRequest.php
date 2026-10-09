<?php

namespace Modules\Store\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class FlashSaleRequest extends FormRequest
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
            'store_id' => 'required|exists:stores,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'max_per_user' => 'nullable|integer|min:1',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.variants' => 'required|array|min:1',
            'products.*.variants.*' => 'exists:product_variants,id',
        ];
    }

    public function messages(): array
    {
        return [
            'store_id.required' => 'حقل المتجر مطلوب.',
            'store_id.exists' => 'المتجر المحدد غير موجود.',
            'name.required' => 'حقل الاسم مطلوب.',
            'start_date.required' => 'تاريخ البدء مطلوب.',
            'end_date.after' => 'يجب أن يكون تاريخ النهاية بعد تاريخ البدء.',
            'discount_type.in' => 'نوع الخصم يجب أن يكون نسبة مئوية أو قيمة ثابتة.',
            'variants.required' => 'يجب تحديد منتجات لتطبيق العرض.',
            'variants.*.exists' => 'أحد المعرفات المرسلة للمنتجات غير موجود.',
        ];
    }
}
