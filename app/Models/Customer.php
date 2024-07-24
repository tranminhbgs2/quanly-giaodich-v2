<?php

namespace App\Models;

use App\Helpers\Constants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes; // Thêm dòng này để sử dụng Soft Deletes
    protected $table = Constants::TABLE_CUSTOMER;
    public $timestamps = true;

    protected $appends = ['status_name'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'phone',
        'address',
        'note',
        'status',
        'created_by',
    ];

    public function getStatusNameAttribute()
    {
        return statusUser($this->status);
    }

    public function getPhoneAttribute($value)
    {
        if ($value) {
            //kiểm tra 2 số đầu nếu = 84 thì đổi thành 0
            if (substr($value, 0, 2) == '84') {
                $value = '0' . substr($value, 2);
            }
            return $value;
        }

        return $value;
    }
}
