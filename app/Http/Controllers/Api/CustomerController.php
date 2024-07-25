<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Constants;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ChangeStatusRequest;
use App\Http\Requests\Customer\DeleteRequest;
use App\Http\Requests\Customer\GetDetailRequest;
use App\Http\Requests\Customer\GetListingRequest;
use App\Http\Requests\Customer\StoreRequest;
use App\Http\Requests\Customer\UpdateRequest;
use App\Repositories\Customer\CustomerRepo;

class CustomerController extends Controller
{
    protected $customer_repo;
    protected $upload_repo;

    public function __construct(CustomerRepo $customerRepo)
    {
        $this->customer_repo = $customerRepo;
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
        $params['page_index'] = request('page_index', 1);
        $params['page_size'] = request('page_size', 10);

        $data = $this->customer_repo->getListing($params, false);
        $total = $this->customer_repo->getListing($params, true);

        return response()->json([
            'code' => 200,
            'error' => 'Danh sách khách hàng',
            'data' => [
                "total_elements" => $total,
                "total_page" => ceil($total / $params['page_size']),
                "page_no" => intval($params['page_index']),
                "page_size" => intval($params['page_size']),
                "data" => $data,
            ],
        ]);
    }

    public function changeStatus(ChangeStatusRequest $request) {
        $params['id'] = request('id', null);
        $params['status'] = request('status', Constants::USER_STATUS_ACTIVE);

        $resutl = $this->customer_repo->changeStatus($params['status'], $params['id']);

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
            $data = $this->customer_repo->getDetail($params);

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
        $params['name'] = request('name', null);
        $params['email'] = request('email', null);
        $params['phone'] = request('phone', null);
        $params['address'] = request('address', null);
        $params['note'] = request('note', null);
        $params['status'] = request('status', Constants::USER_STATUS_ACTIVE);
        $params['created_by'] = auth()->user()->id;

        $resutl = $this->customer_repo->store($params);

        if ($resutl) {
            return response()->json([
                'code' => 200,
                'error' => 'Thêm mới Khách Hàng thành công',
                'data' => null
            ]);
        }

        return response()->json([
            'code' => 400,
            'error' => 'Thêm mới Khách Hàng không thành công',
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
            $params['name'] = request('name', null);
            $params['email'] = request('email', null);
            $params['phone'] = request('phone', null);
            $params['address'] = request('address', null);
            $params['note'] = request('note', null);
            $params['status'] = request('status', Constants::USER_STATUS_ACTIVE);
            $params['created_by'] = auth()->user()->id;

            $resutl = $this->customer_repo->update($params, $params['id']);

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
                $data = $this->customer_repo->delete($params);
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

    public function getAll()
    {
        $data = $this->customer_repo->getAll();
        return response()->json([
            'code' => 200,
            'error' => 'Danh sách khách hàng',
            'data' => $data
        ]);
    }

}
