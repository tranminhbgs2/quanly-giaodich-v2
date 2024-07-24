<?php

namespace App\Http\Requests\Card;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rule = [
            'customer_id' => ['integer', 'min:1'],
            'bank_code' => ['required', 'max:50'],
            'day' => ['integer', 'min:1'],
        ];
        return $rule;
    }

    public function attributes()
    {
        return [
            'customer_id' => 'ID khách hàng',
            'bank_code' => 'Mã ngân hàng',
            'day' => 'Ngày',
        ];
    }

    public function messages()
    {
        return [
            'customer_id.integer' => 'ID khách hàng phải là số nguyên',
            'customer_id.min' => 'ID khách hàng phải là số nguyên dương, nhỏ nhất là 1',

            'bank_code.required' => 'Truyền thiếu tham số bank_code',
            'bank_code.max' => 'Mã ngân hàng tối đa :max ký tự',

            'day.integer' => 'Ngày phải là số nguyên',
            'day.min' => 'Ngày phải là số nguyên dương, nhỏ nhất là 1',
        ];
    }

    /**
     * @param $validator
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
        });
    }

    /**
     * @param Validator $validator
     * @return \Illuminate\Http\JsonResponse
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'code' => 422,
                'error' => $validator->errors()->first(),
                'data' => null
            ])
        );
    }
}
