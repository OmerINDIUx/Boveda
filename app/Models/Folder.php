<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    protected $fillable = ['project_id', 'discipline_id', 'parent_id', 'name'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function discipline()
    {
        return $this->belongsTo(Discipline::class);
    }

    public function parent()
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }
}
