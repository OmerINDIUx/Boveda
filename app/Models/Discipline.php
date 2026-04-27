<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discipline extends Model
{
    protected $fillable = ['name', 'prefix'];

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function folders()
    {
        return $this->hasMany(Folder::class);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }
}
