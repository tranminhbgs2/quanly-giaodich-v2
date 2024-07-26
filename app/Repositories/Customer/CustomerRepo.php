<?php

namespace App\Repositories\Customer;

use App\Helpers\Constants;
use App\Models\Customer;
use App\Models\Fee;
use App\Models\Student;
use App\Models\User;
use App\Repositories\BaseRepo;
use App\Services\Email\MailerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomerRepo extends BaseRepo
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Hàm lấy ds KH, có tìm kiếm và phân trang
     *
     * @param $params
     * @param false $is_counting
     *
     * @return mixed
     */
    public function getListing($params, $is_counting = false)
    {
        $keyword = isset($params['keyword']) ? $params['keyword'] : null;
        $status = isset($params['status']) ? $params['status'] : -1;
        $page_index = isset($params['page_index']) ? $params['page_index'] : 1;
        $page_size = isset($params['page_size']) ? $params['page_size'] : 10;
        $query = Customer::select()->when(!empty($keyword), function ($sql) use ($keyword) {
            $keyword = translateKeyWord($keyword);
            return $sql->where(function ($sub_sql) use ($keyword) {
                $sub_sql->where('name', 'LIKE', "%" . $keyword . "%")
                    ->orWhere('phone', 'LIKE', "%" . $keyword . "%")
                    ->orWhere('note', 'LIKE', "%" . $keyword . "%");
            });
        });

        if ($status > 0) {
            $query->where('status', $status);
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

    /**
     * Hàm tạo thông tin Khách hàng, Nhân viên
     *
     * @param $params
     * @return number
     */
    public function store($params)
    {
        $fillable = [
            'name',
            'email',
            'phone',
            'address',
            'note',
            'status',
            'created_by',
        ];

        $insert = [];

        foreach ($fillable as $field) {
            if (isset($params[$field]) && !empty($params[$field])) {
                $insert[$field] = $params[$field];
            }
        }
        if (!empty($insert['name'])) {
            $res = Customer::create($insert);
            return $res->id;
        }

        return false;
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
        $fillable = [
            'name',
            'email',
            'phone',
            'address',
            'note',
            'status',
            'created_by',
        ];

        $update = [];

        foreach ($fillable as $field) {
            if (isset($params[$field])) {
                $update[$field] = $params[$field];
            }
        }

        return Customer::where('id', $id)->update($update);
    }

    /**
     * Hàm lấy chi tiết thông tin KH
     *
     * @param $params
     */
    public function getDetail($params, $with_trashed=false)
    {
        $id = isset($params['id']) ? $params['id'] : 0;
        $tran = Customer::where('id', $id);

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
     * Hàm khóa thông tin khách hàng vĩnh viễn, khóa trạng thái, ko xóa vật lý
     *
     * @param $params
     * @return array
     */
    public function delete($params)
    {
        $id = isset($params['id']) ? $params['id'] : null;
        $hoKinhDoanh = Customer::find($id);

        if ($hoKinhDoanh) {
            $hoKinhDoanh->status = Constants::USER_STATUS_DELETED;
            $hoKinhDoanh->deleted_at = Carbon::now();

            if ($hoKinhDoanh->save()) {
                return [
                    'code' => 200,
                    'error' => 'Xóa khách hàng thành công',
                    'data' => null
                ];
            } else {
                return [
                    'code' => 400,
                    'error' => 'Xóa khách hàng không thành công',
                    'data' => null
                ];
            }
        } else {
            return [
                'code' => 404,
                'error' => 'Không tìm thấy khách hàng',
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
        $tran = Customer::where('id', $id);

        if ($with_trashed) {
            $tran->withTrashed();
        }

        return $tran->first();
    }

    public function changeStatus($status, $id)
    {
        $update = ['status' => $status];

        return Customer::where('id', $id)->update($update);
    }

    public function getAll()
    {
        return Customer::select('id', 'name', 'phone')->where('created_by', auth()->user()->id)->where('status', Constants::USER_STATUS_ACTIVE)->orderBy('id', 'DESC')->get()->toArray();
    }
}
