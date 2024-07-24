<?php

namespace App\Http\Requests\Customer;

use App\Helpers\Constants;
use App\Models\Customer;
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
            'id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['email', 'max:255'],
            'phone' => ['string', 'max:255'],
            'address' => ['string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
            'status' => ['integer', 'in:' . Constants::USER_STATUS_ACTIVE . ',' . Constants::USER_STATUS_DELETED . ',' . Constants::USER_STATUS_LOCKED ],
        ];

        return $rule;
    }

    public function attributes()
    {
        return [
            'id' => 'ID',
            'name' => 'Tên khách hàng',
            'email' => 'Email',
            'phone' => 'Số điện thoại',
            'address' => 'Địa chỉ',
            'note' => 'Ghi chú',
        ];
    }

    public function messages()
    {
        return [
            'id.required' => 'Truyền thiếu tham số id',
            'id.integer' => 'Tham số id phải là số nguyên',
            'id.min' => 'Tham số id tối thiểu phải là :min',

            'name.required' => 'Truyền thiếu tham số name',
            'name.string' => 'Tham số name phải là chuỗi',
            'name.max' => 'Tham số name tối đa :max ký tự',

            'email.required' => 'Truyền thiếu tham số email',
            'email.email' => 'Tham số email không đúng định dạng',
            'email.max' => 'Tham số email tối đa :max ký tự',

            'phone.required' => 'Truyền thiếu tham số phone',
            'phone.string' => 'Tham số phone phải là chuỗi',
            'phone.max' => 'Tham số phone tối đa :max ký tự',

            'address.required' => 'Truyền thiếu tham số address',
            'address.string' => 'Tham số address phải là chuỗi',
            'address.max' => 'Tham số address tối đa :max ký tự',

            'note.string' => 'Tham số note phải là chuỗi',
            'note.max' => 'Tham số note tối đa :max ký tự',
        ];
    }

    /**
     * @param $validator
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check tồn tại
            $user = Customer::where('id', $this->request->get('id'))->withTrashed()->first();
            if ($user) {
                if ($user->status == Constants::USER_STATUS_DELETED) {
                    $validator->errors()->add('check_exist', 'Thông tin khách hàng đã bị khóa vĩnh viễn, không thể cập nhật');
                }
            } else {
                $validator->errors()->add('check_exist', 'Không tìm thấy thông tin khách hàng');
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
