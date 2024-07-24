<?php

namespace App\Http\Requests\Card;

use App\Helpers\Constants;
use App\Models\Card;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateRequest extends FormRequest
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
            'status' => ['integer', 'in:' . Constants::USER_STATUS_ACTIVE . ',' . Constants::USER_STATUS_DELETED . ',' . Constants::USER_STATUS_LOCKED ],
        ];

        return $rule;
    }

    public function attributes()
    {
        return [
            'id' => 'ID',
            'customer_id' => 'ID khách hàng',
            'bank_code' => 'Mã ngân hàng',
            'day' => 'Ngày',
            'status' => 'Trạng thái',
        ];
    }

    public function messages()
    {
        return [
            'id.required' => 'Truyền thiếu tham số id',
            'id.integer' => 'Tham số id phải là số nguyên',
            'id.min' => 'Tham số id tối thiểu phải là :min',

            'customer_id.integer' => 'ID khách hàng phải là số nguyên',
            'customer_id.min' => 'ID khách hàng phải là số nguyên dương, nhỏ nhất là 1',

            'bank_code.required' => 'Truyền thiếu tham số bank_code',
            'bank_code.max' => 'Mã ngân hàng tối đa :max ký tự',

            'day.integer' => 'Ngày phải là số nguyên',
            'day.min' => 'Ngày phải là số nguyên dương, nhỏ nhất là 1',

            'status.integer' => 'Trạng thái phải là số nguyên',
            'status.in' => 'Trạng thái không hợp lệ',
        ];
    }

    /**
     * @param $validator
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check tồn tại
            $user = Card::where('id', $this->request->get('id'))->withTrashed()->first();
            if ($user) {
                if ($user->status == Constants::USER_STATUS_DELETED) {
                    $validator->errors()->add('check_exist', 'Thông tin thẻ đã bị khóa vĩnh viễn, không thể cập nhật');
                }
            } else {
                $validator->errors()->add('check_exist', 'Không tìm thấy thông tin thẻ');
            }
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
