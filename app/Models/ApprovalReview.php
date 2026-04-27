<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalReview extends Model
{
    protected $fillable = ['approval_request_id', 'approval_step_id', 'reviewer_id', 'status', 'comments'];

    public function request()
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    public function step()
    {
        return $this->belongsTo(ApprovalStep::class, 'approval_step_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
