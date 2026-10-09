<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;

class TicketReturn extends Model
{
    protected $fillable = [
        'ticket_id',
        'preferred_solution', // refund | replacement
        'decision',           // pending | approved | denied
        'reason',
        'description',
        'approved_at',
        'processed_at',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function items()
    {
        return $this->hasMany(TicketReturnItem::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(ReturnAuditLog::class, 'return_request_id');
    }
}
