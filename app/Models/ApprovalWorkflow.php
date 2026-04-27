<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalWorkflow extends Model
{
    protected $fillable = ['project_id', 'name', 'description'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function steps()
    {
        return $this->hasMany(ApprovalStep::class)->orderBy('order');
    }
}
