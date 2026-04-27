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
}
