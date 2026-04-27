<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RfiAttachment extends Model
{
    use HasFactory;

    protected $fillable = ['rfi_id', 'rfi_response_id', 'file_path', 'file_name'];

    public function rfi()
    {
        return $this->belongsTo(Rfi::class);
    }

    public function rfiResponse()
    {
        return $this->belongsTo(RfiResponse::class);
    }
}
