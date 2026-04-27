<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transmittal extends Model
{
    protected $fillable = [
        'project_id',
        'code',
        'subject',
        'message',
        'sender_name',
        'recipient_name',
        'recipient_email',
        'status'
    ];

    public function items()
    {
        return $this->hasMany(TransmittalItem::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
