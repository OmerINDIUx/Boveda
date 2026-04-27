<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalRequest extends Model
{
    protected $fillable = ['file_revision_id', 'approval_workflow_id', 'current_step_id', 'status'];

    public function fileRevision()
    {
        return $this->belongsTo(FileRevision::class);
    }

    public function workflow()
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'approval_workflow_id');
    }

    public function currentStep()
    {
        return $this->belongsTo(ApprovalStep::class, 'current_step_id');
    }

    public function reviews()
    {
        return $this->hasMany(ApprovalReview::class);
    }
}
