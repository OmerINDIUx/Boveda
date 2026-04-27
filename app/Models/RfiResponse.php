<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RfiResponse extends Model
{
    use HasFactory;

    protected $fillable = ['rfi_id', 'user_id', 'message'];

    public function rfi()
    {
        return $this->belongsTo(Rfi::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attachments()
    {
        return $this->hasMany(RfiAttachment::class);
    }
}
