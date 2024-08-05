<?php

namespace App\Repositories\User;

use App\Events\ActionLogEvent;
use App\Helpers\Constants;
use App\Models\User;
use App\Repositories\BaseRepo;
use App\Services\Email\MailerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserRepo extends BaseRepo
{
    public function getListing($params, $is_counting = false)
    {
        $status = $params['status'] ?? -1;
        $page_index = $params['page_index'] ?? 1;
        $page_size = $params['page_size'] ?? 10;
        $date_from = $params['date_from'] ?? null;
        $date_to = $params['date_to'] ?? null;

        $query = User::select();

        if ($date_from && $date_to) {
            $query->whereBetween('created_at', [$date_from, $date_to]);
        }

        if ($status >= 0) {
            $query->where('status', $status);
        } else {
            $query->where('status', '!=', Constants::USER_STATUS_DELETED);
        }

        if ($is_counting) {
            return $query->count();
        } else {
            $offset = ($page_index - 1) * $page_size;
            if ($page_size > 0 && $offset >= 0) {
                $query->take($page_size)->skip($offset);
            }
        }

        $query->orderBy('id', 'DESC');

        return $query->get()->toArray();
    }

    public function store($params)
    {
        $fullname = isset($params['fullname']) ? $params['fullname'] : null;
        $phone = isset($params['phone']) ? $params['phone'] : null;
        $email = isset($params['email']) ? $params['email'] : null;
        $birthday = isset($params['birthday']) ? $params['birthday'] : null;
        $department_id = isset($params['department_id']) ? $params['department_id'] : null;
        $username = isset($params['username']) ? $params['username'] : null;
        $password = isset($params['password']) ? $params['password'] : null;
        $status = isset($params['status']) ? $params['status'] : Constants::USER_STATUS_ACTIVE;
        $account_type = isset($params['account_type']) ? $params['account_type'] : Constants::ACCOUNT_TYPE_STAFF;

        if ($fullname && $username && $password) {
            $user = new User();
            $user->fill([
                'account_type' => $account_type,
                'username' => $username,
                'fullname' => $fullname,
                'birthday' => $birthday,
                'address' => null,
                'email' => $email,
                'phone' => formatMobile($phone),
                'display_name' => $fullname,
                'password' => Hash::make($password),
                'notes' => null,
                'department_id' => $department_id,
                'status' => $status
            ]);

            if ($user->save()) {
                return $user->id;
            }

        }

        return 0;
    }

    /**
     * Hàm cập nhật thông tin KH theo id
     *
     * @param $params
     * @param $id
     * @return bool
     */
    public function update($params, $id)
    {
        $update = [];

        (isset($params['fullname']) && $params['fullname']) ? $update['fullname'] = $params['fullname'] : null;
        (isset($params['fullname']) && $params['fullname']) ? $update['display_name'] = $params['fullname'] : null;

        (isset($params['phone']) && $params['phone']) ? $update['phone'] = $params['phone'] : null;
        (isset($params['email']) && $params['email']) ? $update['email'] = $params['email'] : null;
        (isset($params['avatar']) && $params['avatar']) ? $update['avatar'] = $params['avatar'] : null;
        (isset($params['birthday']) && $params['birthday']) ? $update['birthday'] = $params['birthday'] : null;

        (isset($params['department_id']) && $params['department_id']) ? $update['department_id'] = $params['department_id'] : null;
        (isset($params['identifier']) && $params['identifier']) ? $update['identifier'] = $params['identifier'] : null;
        (isset($params['issue_date']) && $params['issue_date']) ? $update['issue_date'] = $params['issue_date'] : null;
        (isset($params['issue_place']) && $params['issue_place']) ? $update['issue_place'] = $params['issue_place'] : null;
        (isset($params['address']) && $params['address']) ? $update['address'] = $params['address'] : null;

        (isset($params['status']) && $params['status']) ? $update['status'] = $params['status'] : null;
        (isset($params['account_type']) && $params['account_type']) ? $update['account_type'] = $params['account_type'] : null;

        $user = User::where('id', $id)->update($update);
        if ($user) {
            return true;
        }

        return false;
    }

    public function delete($params)
    {
        $id = isset($params['id']) ? $params['id'] : null;
        $bankAccount = User::find($id);

        if ($bankAccount) {
            $bankAccount->status = Constants::USER_STATUS_DELETED;
            $bankAccount->deleted_at = Carbon::now();

            if ($bankAccount->save()) {
                return [
                    'code' => 200,
                    'error' => 'Xóa user thành công',
                    'data' => null
                ];
            } else {
                return [
                    'code' => 400,
                    'error' => 'Xóa user không thành công',
                    'data' => null
                ];
            }
        } else {
            return [
                'code' => 404,
                'error' => 'Không tìm thấy tài khoản ngân hàng',
                'data' => null
            ];
        }
    }

    /**
     * Hàm lấy chi tiết thông tin GD
     *
     * @param $params
     */
    public function getDetail($params, $with_trashed = false)
    {
        $id = isset($params['id']) ? $params['id'] : 0;
        $tran = User::where('id', $id)->with(['userPermissions' => function ($query) {
            $query->select('positions.id as action_id', 'positions.name as action_name', 'positions.code as action_code'); // Chọn các trường cụ thể từ bảng positions
        }]);

        if ($with_trashed) {
            $tran->withTrashed();
        }

        $data = $tran->first();

        if ($data) {

            return [
                'code' => 200,
                'error' => 'Thông tin chi tiết',
                'data' => $data
            ];
        } else {
            return [
                'code' => 404,
                'error' => 'Không tìm thấy thông tin chi tiết ',
                'data' => null
            ];
        }
    }

    /**
     * Hàm lấy chi tiết thông tin GD
     *
     * @param $params
     */
    public function getById($id, $with_trashed = false)
    {
        $tran = User::where('id', $id)->with(['userPermissions' => function ($query) {
            $query->select('positions.id as action_id', 'positions.name as action_name', 'positions.code as action_code'); // Chọn các trường cụ thể từ bảng positions
        }]);

        if ($with_trashed) {
            $tran->withTrashed();
        }

        return $tran->first();
    }

    public function changeStatus($status, $id)
    {

        $update = ['status' => $status];

        return User::where('id', $id)->update($update);
    }

    public function getRoles($user_id = null)
    {
        $group_id = auth()->user()->group_id;
        // $query = Group::where('id', $group_id)->where('is_active', 1);
        // $query->with([
        //     'roles' => function($sql){
        //         $sql->select('*');
        //     }
        // ]);

        // $group = $query->get();

        // if (isset($group[0])) {
        //     $roles =  collect($group[0]->roles)->map(function ($item){
        //         return $item->code;
        //     })->all();
        // } else {
        //     $roles = [];
        // }
        // return $roles;
    }

    public function getPermission($user_id = null)
    {
        // $user_id = auth()->id;
        // echo 'a';
        $query = User::where('id', Auth::id());
        $query->with([
            'permissions' => function ($sql) {
                $sql->select('*');
            }
        ]);
        $result = $query->get();
        if (isset($result[0])) {
            $permissions =  collect($result[0]->permissions)->map(function ($item) {
                return $item->code;
            })->all();
        } else {
            $permissions = [];
        }

        // print_r($permissions);
        return $permissions;
    }

    public function getPermissionUser($user_id = null)
    {
        // $user_id = auth()->id;
        // echo 'a';
        $query = User::where('id', $user_id);
        $query->with([
            'permissions' => function ($sql) {
                $sql->select('*');
            }
        ]);
        $result = $query->get();
        if (isset($result[0])) {
            $data['permissions'] =  collect($result[0]->permissions)->map(function ($item) {
                return $item->permission_id;
            })->all();
            $roles = collect($result[0]->permissions)->map(function ($item) {
                return $item->role_id;
            })->all();
            $roles = collect($roles)->unique();
            $data['roles'] = $roles->values()->all();
        } else {
            $data = [
                'permissions' => [],
                'roles' => []
            ];
        }
        // print_r($permissions);
        return $data;
    }
    public function getAllStaff()
    {
        return User::select('id', 'fullname', 'status', 'balance')->where('status', Constants::USER_STATUS_ACTIVE)->whereIn('account_type', [Constants::ACCOUNT_TYPE_STAFF, Constants::ACCOUNT_TYPE_ACCOUNTANT])->orderBy('id', 'DESC')->get()->toArray();
    }

    public function updateBalance($id, $balance, $action)
    {
        $user = User::where('id', $id)->first();
        // Lưu log qua event
        event(new ActionLogEvent([
            'actor_id' => auth()->user()->id ?? 0,
            'username' => auth()->user()->username ?? 0,
            'action' => 'UPDATE_BANLANCE_USER',
            'description' => $action . ' Cập nhật số tiền cho User ' . $user->username . ' từ ' . $user->balance . ' thành ' . $balance,
            'data_new' => $balance,
            'data_old' => $user->balance,
            'model' => 'User',
            'table' => 'users',
            'record_id' => $id,
            'ip_address' => request()->ip()
        ]));

        $update = ['balance' => $balance];
        return $user->update($update);
    }

    public function getUserPermissions($userId)
    {
        $user = User::with(['userPermissions.groupRule'])->find($userId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $result = [];

        foreach ($user->userPermissions as $position) {
            $departmentId = $position->groupRule->id;
            $departmentName = $position->groupRule->name;
            $function_code = $position->groupRule->code;
            $path = $position->groupRule->url;

            // Check if department already exists in result
            if (!isset($result[$departmentId])) {
                $result[$departmentId] = [
                    'id' => $departmentId,
                    'function_code' => $function_code,
                    'function_name' => $departmentName,
                    'path' => $path,
                    'actions' => []
                ];
            }

            // Add position action to the department's actions list
            $result[$departmentId]['actions'][] = [
                'action_id' => $position->id,
                'action_name' => $position->name,
                'action_code' => $position->code,
                'path' => $position->url
            ];
        }

        // Reindex permissions array by removing keys
        $result = array_values($result);

        return $result;
    }
        public function changePassword($params, $user_obj)
    {
        $password = isset($params['password']) ? $params['password'] : null;

        if (is_object($user_obj) && $user_obj && $password) {
            $user_obj->password = Hash::make($password);

            if ($user_obj->save()) {
                return true;
            }
        }

        return false;
    }
    /**
     * Hàm reset lại mật khẩu
     *
     * @param $params
     * @return bool
     */
    public function resetPassword($params)
    {
        $receiver_by = isset($params['email']) ? $params['email'] : null;

        if ($receiver_by) {
            $new_password = strval(rand(10000000, 99999999));

            $body = getEmailBody(Constants::EMAIL_TYPE_RESET_PASSWORD, [
                'email' => $receiver_by,
                'content' => $new_password
            ]);

            $mailer = new MailerService();
            $res = $mailer->sendSingle($receiver_by, 'Thiết lập lại mật khẩu - ' . $new_password, $body);

            if ($res == 1) {
                User::where([
                    'email' => $receiver_by
                ])->update([
                    'password' => Hash::make($new_password)
                ]);

                return true;
            }
        }

        return false;

    }
    /**
     * Hàm tạo mật khẩu để lấy mã OTP
     *
     * @param $password
     * @param $customer
     * @return bool
     */
    public function storeOtpPassword($password, $customer)
    {
        if ($password && is_object($customer)) {
            $customer->otp_password = Hash::make($password);
            $customer->otp_created_at = Carbon::now();

            if ($customer->save()) {
                return true;
            }
        }

        return false;

    }

    /**
     * Hàm cập nhật mk lấy mã OTP
     *
     * @param $password
     * @param $customer
     * @return bool
     */
    public function updateOtpPassword($password, $customer)
    {
        if ($password && is_object($customer)) {
            $customer->otp_password = Hash::make($password);
            $customer->otp_created_at = Carbon::now();

            if ($customer->save()) {
                return true;
            }
        }

        return false;

    }

    public function attachPositions($userId, array $positionIds)
    {
        $department = User::find($userId);
        return $department->userPermissions()->attach($positionIds);
    }

    public function detachAllPositions($userId)
    {
        $department = User::find($userId);
        return $department->userPermissions()->detach();
    }

    public function getByEmail($email)
    {
        return User::where('email', $email)->first();
    }
}
