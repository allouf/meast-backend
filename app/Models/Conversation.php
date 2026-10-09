<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['initiator_id', 'receiver_id','name', 'status_by_initiator','status_by_receiver'];

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function callDetail()
    {
        return $this->hasMany(CallDetail::class);
    }
}
