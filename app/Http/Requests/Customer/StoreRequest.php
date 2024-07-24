<?php

namespace App\Http\Requests\Customer;

use App\Helpers\Constants;
use App\Models\Customer;
use App\Models\Student;
use App\Models\User;
use App\Rules\PasswordRule;
use App\Rules\UsernameRule;
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
            'name' => 'required|string|max:255',
            'email' => 'email|max:255',
            'phone' => 'string|max:255',
            'address' => 'string|max:255',
            'note' => 'nullable|string|max:255',
        ];
        return $rule;
    }

    public function attributes()
    {
        return [
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
            'name.required' => 'Truyền thiếu tham số name',
            'name.string' => 'Tham số name phải là chuỗi',
            'name.max' => 'Tham số name tối đa :max ký tự',

            'email.email' => 'Tham số email không đúng định dạng',
            'email.max' => 'Tham số email tối đa :max ký tự',

            'phone.string' => 'Tham số phone phải là chuỗi',
            'phone.max' => 'Tham số phone tối đa :max ký tự',

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
            // Check username
            $user = Customer::where('name', $this->request->get('name'))->withTrashed()->first();

            if ($user) {
                $validator->errors()->add('check_exist', 'Tên khách hàng đã được đăng ký. Bạn vui lòng, chọn tên khác');
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
