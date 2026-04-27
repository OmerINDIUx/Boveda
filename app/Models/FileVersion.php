<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileVersion extends Model
{
    protected $fillable = [
        'document_id',
        'version_number',
        'file_name',
        'original_name',
        'path',
        'extension',
        'size',
        'user_id',
        'notes',
        'revision',
        'status',
        'discipline',
        'document_number'
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
