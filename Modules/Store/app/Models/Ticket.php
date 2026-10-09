<?php

namespace Modules\Store\Models;

use Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'user_id',
        'issue_type_id',
        'priority',
        'subject',
        'status',
        'sla_response_hours',
        'first_response_at',
    ];

    protected $casts = [
        'first_response_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems()
    {
        return $this->belongsToMany(
            OrderItem::class,
            'ticket_order_items'
        );
    }


    public function issueType()
    {
        return $this->belongsTo(IssueType::class);
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function returnRequest()
    {
        return $this->hasOne(TicketReturn::class);
    }
    public function isOpen()
    {
        return $this->status === 'OPEN';
    }

    public function isAnswered()
    {
        return $this->status === 'ANSWERED';
    }

    public function isResolved()
    {
        return $this->status === 'RESOLVED';
    }

    public function getSlaDueAtAttribute()
    {
        return $this->created_at?->addHours($this->sla_response_hours);
    }

    public function getIsSlaBreachedAttribute()
    {
        if ($this->first_response_at) {
            return false;
        }

        return now()->greaterThan($this->sla_due_at);
    }

    public function getSlaDelayMinutesAttribute()
    {
        if (!$this->is_sla_breached) {
            return 0;
        }

        return $this->sla_due_at->diffInMinutes(now());
    }

    public function getSlaDelayFormattedAttribute()
    {
        if (!$this->is_sla_breached) {
            return "0m";
        }

        $minutes = $this->sla_due_at->diffInMinutes(now());
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$remainingMinutes}m";
        }

        return "{$remainingMinutes}m";
    }
}
