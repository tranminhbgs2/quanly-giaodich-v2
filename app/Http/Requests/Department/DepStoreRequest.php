<?php

namespace App\Http\Requests\Department;

use App\Helpers\Constants;
use App\Models\Department;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DepStoreRequest extends FormRequest
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
            'name' => ['required'],
            'code' => ['required'],
            'url' => ['required', ],
            'is_default' => ['required', 'boolean'],

        ];

        return $rule;
    }

    public function attributes()
    {
        return [
            'name' => 'Tên nhóm quyền',
            'code' => 'Mã nhóm quyền',
            'url' => 'Đường dẫn',
            'is_default' => 'Mặc định',];
    }

    public function messages()
    {
        return [
            'required' => ':attribute không được để trống',
            'boolean' => ':attribute phải là true hoặc false',];
    }

    /**
     * @param $validator
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check username
            $dep = Department::where('name', $this->request->get('name'))->withTrashed()->first();

            if ($dep) {
                $validator->errors()->add('check_exist', 'Tên nhóm quyền đã tồn tại');
            }

            $dep_code = Department::where('code', $this->request->get('code'))->withTrashed()->first();
            if ($dep_code) {
                $validator->errors()->add('check_exist', 'Mã nhóm quyền đã tồn tại');
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
