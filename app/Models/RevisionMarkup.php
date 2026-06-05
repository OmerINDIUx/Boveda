<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevisionMarkup extends Model
{
    protected $fillable = [
        'file_revision_id',
        'user_id',
        'page_number',
        'tool',
        'label',
        'comment',
        'x_percent',
        'y_percent',
        'color',
        'stroke_width',
        'snapshot_path',
    ];

    public function revision()
    {
        return $this->belongsTo(FileRevision::class, 'file_revision_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
