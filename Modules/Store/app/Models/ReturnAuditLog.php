<?php

namespace Modules\Store\Models;

use Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;

class ReturnAuditLog extends Model
{
    protected $fillable = [
        'return_request_id',
        'action',
        'note',
        'by',
    ];

    public function returnRequest()
    {
        return $this->belongsTo(TicketReturn::class, 'return_request_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'by');
    }
}
