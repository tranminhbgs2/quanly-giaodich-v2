<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Constants;
use App\Http\Controllers\Controller;
use App\Http\Requests\Card\ChangeStatusProccessRequest;
use App\Http\Requests\Card\ChangeStatusRequest;
use App\Http\Requests\Card\DeleteRequest;
use App\Http\Requests\Card\GetDetailRequest;
use App\Http\Requests\Card\GetListingRequest;
use App\Http\Requests\Card\StoreRequest;
use App\Http\Requests\Card\UpdateRequest;
use App\Repositories\Card\CardRepo;

class CardController extends Controller
{
    protected $card_repo;

    public function __construct(CardRepo $cardRepo)
    {
        $this->card_repo = $cardRepo;
    }

    /**
     * API lấy ds khách hàng
     * URL: {{url}}/api/v1/customers
     *
     * @param GetListingRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getListing(GetListingRequest $request)
    {
        $params['keyword'] = request('keyword', null);
        $params['status'] = request('status', -1);
        $params['status_proccess'] = request('status_proccess', -1);
        $params['customer_id'] = request('customer_id', -1);
        $params['bank_code'] = request('bank_code', null);
        $params['type_card'] = request('type_card', null);
        $params['day'] = request('day', 0);
        $params['page_index'] = request('page_index', 1);
        $params['page_size'] = request('page_size', 10);

        $data = $this->card_repo->getListing($params, false);
        $total = $this->card_repo->getListing($params, true);

        $current_day = now()->day; // Lấy ngày hiện tại

        $less_than_current = [];
        $equal_to_current = [];
        $greater_than_current = [];

        // Phân chia mảng thành ba phần
        foreach ($data as $item) {
            if ($item['day'] < $current_day) {
                $less_than_current[] = $item;
            } elseif ($item['day'] == $current_day) {
                $equal_to_current[] = $item;
            } else {
                $greater_than_current[] = $item;
            }
        }

        // Sắp xếp mỗi phần riêng biệt
        usort($less_than_current, function ($a, $b) {
            return $a['day'] - $b['day'];
        });

        usort($greater_than_current, function ($a, $b) {
            return $a['day'] - $b['day'];
        });

        // Kết hợp các phần lại theo thứ tự mong muốn
        $sorted_array = array_merge($less_than_current, $equal_to_current, $greater_than_current);

        return response()->json([
            'code' => 200,
            'error' => 'Danh sách thẻ',
            'data' => [
                "total_elements" => $total,
                "total_page" => ceil($total / $params['page_size']),
                "page_no" => intval($params['page_index']),
                "page_size" => intval($params['page_size']),
                "data" => $data,
            ],
        ]);
    }

    public function changeStatus(ChangeStatusRequest $request)
    {
        $params['id'] = request('id', null);
        $params['status'] = request('status', Constants::USER_STATUS_ACTIVE);

        $resutl = $this->card_repo->changeStatus($params['status'], $params['id']);

        if ($resutl) {
            return response()->json([
                'code' => 200,
                'error' => 'Cập nhật trạng thái thành công',
                'data' => null
            ]);
        }

        return response()->json([
            'code' => 400,
            'error' => 'Cập nhật trạng thái không thành công',
            'data' => null
        ]);
    }

    /**
     * API lấy thông tin chi tiết khách hàng
     * URL: {{url}}/api/v1/customers/detail/8
     *
     * @param GetDetailRequest $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDetail(GetDetailRequest $request, $sscid)
    {
        if ($sscid) {
            $params['id'] = request('id', null);
            $data = $this->card_repo->getDetail($params);
        } else {
            $data = [
                'code' => 422,
                'error' => 'Truyền thiếu ID',
                'data' => null
            ];
        }

        return response()->json($data);
    }

    /**
     * API thêm mới KH từ CMS
     * URL: {{url}}/api/v1/customers/store
     *
     * @param StoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreRequest $request)
    {
        $params['customer_id'] = request('customer_id', null);
        $params['bank_code'] = request('bank_code', null);
        $params['type_card'] = request('type_card', null);
        $params['status_proccess'] = request('status_proccess', 1);
        $params['status'] = request('status', Constants::USER_STATUS_ACTIVE);
        $params['number_card'] = request('number_card', null);
        $params['day'] = request('day', 0);
        $params['limit'] = request('limit', 0);
        $params['created_by'] = auth()->user()->id;

        $resutl = $this->card_repo->store($params);
        if ($resutl) {
            return response()->json([
                'code' => 200,
                'error' => 'Thêm mới thẻ thành công',
                'data' => null
            ]);
        }

        return response()->json([
            'code' => 400,
            'error' => 'thêm mới thẻ không thành công',
            'data' => null
        ]);
    }

    /**
     * API cập nhật thông tin KH theo id
     * URL: {{url}}/api/v1/customers/update/id
     *
     * @param UpdateRequest $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRequest $request)
    {
        $params['id'] = request('id', null);
        if ($params['id']) {
            $params['customer_id'] = request('customer_id', null);
            $params['bank_code'] = request('bank_code', null);
            $params['type_card'] = request('type_card', null);
            $params['status_proccess'] = request('status_proccess', 1);
            $params['status'] = request('status', Constants::USER_STATUS_ACTIVE);
            $params['number_card'] = request('number_card', null);
            $params['day'] = request('day', 0);
            $params['limit'] = request('limit', 0);
            $params['created_by'] = auth()->user()->id;

            $resutl = $this->card_repo->update($params, $params['id']);

            if ($resutl) {
                return response()->json([
                    'code' => 200,
                    'error' => 'Cập nhật thông tin thành công',
                    'data' => null
                ]);
            }

            return response()->json([
                'code' => 400,
                'error' => 'Cập nhật thông tin không thành công',
                'data' => null
            ]);
        } else {
            return response()->json([
                'code' => 422,
                'error' => 'ID không hợp lệ',
                'data' => null
            ]);
        }
    }

    /**
     * API xóa thông tin khách hàng, xóa trạng thái, ko xóa vật lý
     * URL: {{url}}/api/v1/customers/delete/1202112817000308
     *
     * @param DeleteRequest $request
     * @param $sscid
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(DeleteRequest $request, $id)
    {
        if ($id) {
            $params['id'] = request('id', null);
            if ($id == $params['id']) {
                $data = $this->card_repo->delete($params);
            } else {
                return response()->json([
                    'code' => 422,
                    'error' => 'ID không hợp lệ',
                    'data' => null
                ]);
            }
        } else {
            $data = [
                'code' => 422,
                'error' => 'Truyền thiếu ID',
                'data' => null
            ];
        }

        return response()->json($data);
    }


    public function changeStatusProccess(ChangeStatusProccessRequest $request)
    {
        $params['id'] = request('id', null);
        $params['status_proccess'] = request('status_proccess', Constants::USER_STATUS_ACTIVE);

        $resutl = $this->card_repo->changeStatusProccess($params);

        if ($resutl) {
            return response()->json([
                'code' => 200,
                'error' => 'Cập nhật trạng thái thành công',
                'data' => null
            ]);
        }

        return response()->json([
            'code' => 400,
            'error' => 'Cập nhật trạng thái không thành công',
            'data' => null
        ]);
    }

    public function getAll()
    {
        $data = $this->card_repo->getAll();
        return response()->json([
            'code' => 200,
            'error' => 'Danh sách thẻ',
            'data' => $data
        ]);
    }
}
