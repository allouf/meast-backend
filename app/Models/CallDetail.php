<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'start_call_date',
        'end_call_date',
        'call_type'
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}