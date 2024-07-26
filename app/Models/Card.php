<?php

namespace App\Models;

use App\Helpers\Constants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Card extends Model
{
    use SoftDeletes; // Thêm dòng này để sử dụng Soft Deletes
    protected $table = Constants::TABLE_CARD;
    public $timestamps = true;

    protected $appends = ['status_name', 'status_proccess_name'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
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

    public function getStatusNameAttribute()
    {
        return statusUser($this->status);
    }

    public function getStatusProccessNameAttribute()
    {
        $name = '';
        if ($this->status_proccess == 2) {
            $name = 'Đã xử lý';
        } else if ($this->status_proccess == 3) {
            $name = 'Sắp đến hạn';
        }  else if ($this->status_proccess == 4) {
            $name = 'Đã quá hạn';
        } else {
            $name = 'Chưa xử lý';
        }
        return $name;
    }

    public function cus()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }
}
