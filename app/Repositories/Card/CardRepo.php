<?php

namespace App\Repositories\Card;

use App\Helpers\Constants;
use App\Models\Card;
use App\Repositories\BaseRepo;
use Carbon\Carbon;

class CardRepo extends BaseRepo
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
        $status_proccess = isset($params['status_proccess']) ? $params['status_proccess'] : -1;
        $customer_id = isset($params['customer_id']) ? $params['customer_id'] : -1;
        $bank_code = isset($params['bank_code']) ? $params['bank_code'] : null;
        $type_card = isset($params['type_card']) ? $params['type_card'] : null;
        $day = isset($params['day']) ? $params['day'] : 0;
        $page_index = isset($params['page_index']) ? $params['page_index'] : 1;
        $page_size = isset($params['page_size']) ? $params['page_size'] : 10;

        $query = Card::select()->with([
            'cus' => function ($sql) {
                $sql->select(['id', 'name', 'status']);
            },
        ]);

        $query->when(!empty($keyword), function ($sql) use ($keyword) {
            $keyword = translateKeyWord($keyword);
            return $sql->where(function ($sub_sql) use ($keyword) {
                $sub_sql->where('number_card', 'LIKE', "%" . $keyword . "%");
            });
        });

        if ($status > 0) {
            $query->where('status', $status);
        } else {
            $query->where('status', Constants::USER_STATUS_ACTIVE);
        }

        if ($status_proccess > 0) {
            $query->where('status_proccess', $status_proccess);
        }

        if ($customer_id > 0) {
            $query->where('customer_id', $customer_id);
        }

        if (!empty($bank_code)) {
            $query->where('bank_code', $bank_code);
        }

        if (!empty($type_card)) {
            $query->where('type_card', $type_card);
        }

        if ($day > 0) {
            $query->where('day', $day);
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
            'customer_id',
            'bank_code',
            'type_card',
            'number_card',
            'day',
            'limit',
            'status_proccess',
            'status',
            'created_by',
        ];

        $insert = [];

        foreach ($fillable as $field) {
            if (isset($params[$field]) && !empty($params[$field])) {
                $insert[$field] = $params[$field];
            }
        }
        if (!empty($insert['bank_code'])) {
            $res = Card::create($insert);
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
            'customer_id',
            'bank_code',
            'type_card',
            'number_card',
            'day',
            'limit',
            'status_proccess',
            'status',
            'created_by',
        ];

        $update = [];

        foreach ($fillable as $field) {
            if (isset($params[$field])) {
                $update[$field] = $params[$field];
            }
        }

        return Card::where('id', $id)->update($update);
    }

    /**
     * Hàm lấy chi tiết thông tin KH
     *
     * @param $params
     */
    public function getDetail($params, $with_trashed = false)
    {
        $id = isset($params['id']) ? $params['id'] : 0;
        $tran = Card::where('id', $id);

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
        $hoKinhDoanh = Card::find($id);

        if ($hoKinhDoanh) {
            $hoKinhDoanh->status = Constants::USER_STATUS_DELETED;
            $hoKinhDoanh->deleted_at = Carbon::now();

            if ($hoKinhDoanh->save()) {
                return [
                    'code' => 200,
                    'error' => 'Xóa thẻ thành công',
                    'data' => null
                ];
            } else {
                return [
                    'code' => 400,
                    'error' => 'Xóa thẻ không thành công',
                    'data' => null
                ];
            }
        } else {
            return [
                'code' => 404,
                'error' => 'Không tìm thấy thẻ',
                'data' => null
            ];
        }
    }

    public function changeStatusProccess($params)
    {
        $id = isset($params['id']) ? $params['id'] : null;
        $status_proccess = isset($params['status_proccess']) ? $params['status_proccess'] : 1;
        $hoKinhDoanh = Card::find($id);

        if ($hoKinhDoanh) {
            $hoKinhDoanh->status_proccess = $status_proccess;

            if ($hoKinhDoanh->save()) {
                return [
                    'code' => 200,
                    'error' => 'Cập nhật trạng thái xử lý thẻ thành công',
                    'data' => null
                ];
            } else {
                return [
                    'code' => 400,
                    'error' => 'Cập nhật trạng thái xử lý thẻ không thành công',
                    'data' => null
                ];
            }
        } else {
            return [
                'code' => 404,
                'error' => 'Không tìm thấy thẻ',
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
        $tran = Card::where('id', $id);

        if ($with_trashed) {
            $tran->withTrashed();
        }

        return $tran->first();
    }

    public function changeStatus($status, $id)
    {
        $update = ['status' => $status];

        return Card::where('id', $id)->update($update);
    }

    public function getAll()
    {
        return Card::select('id', 'name', 'phone')->where('status', Constants::USER_STATUS_ACTIVE)->orderBy('id', 'DESC')->get()->toArray();
    }
}
