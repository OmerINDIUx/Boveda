<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevisionNote extends Model
{
    protected $fillable = ['file_revision_id', 'user_id', 'content'];

    public function revision()
    {
        return $this->belongsTo(FileRevision::class, 'file_revision_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
