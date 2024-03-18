<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $table = 'vouchers';

    protected $fillable = [
        'id',
        'submitter_id',
        'submitter_object',
        'total_amount',
        'object_type',
        'created_at',
        'updated_at',
    ];
}
