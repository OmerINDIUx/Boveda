<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'project_id',
        'discipline_id',
        'document_number',
        'title',
        'status',
        'is_locked',
        'approval_status'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function discipline()
    {
        return $this->belongsTo(Discipline::class);
    }

    public function revisions()
    {
        return $this->hasMany(FileRevision::class);
    }

    public function latestRevision()
    {
        return $this->hasOne(FileRevision::class)->where('is_current', true);
    }
}
