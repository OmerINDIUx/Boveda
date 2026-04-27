<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalStep extends Model
{
    protected $fillable = ['approval_workflow_id', 'name', 'user_id', 'order'];

    public function workflow()
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'approval_workflow_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
