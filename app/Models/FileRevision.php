<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileRevision extends Model
{
    protected $fillable = [
        'document_id',
        'revision_code',
        'status',
        'file_path',
        'original_name',
        'extension',
        'size',
        'user_id',
        'change_notes',
        'is_current'
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function notes()
    {
        return $this->hasMany(RevisionNote::class)->latest();
    }
}
