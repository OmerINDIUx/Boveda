<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'client_name',
        'construction_location',
        'owner_user_id',
        'manager_user_id',
        'start_date',
        'target_date',
        'contract_number',
        'project_stage',
        'priority_level',
    ];

    protected $casts = [
        'start_date' => 'date',
        'target_date' => 'date',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function disciplines()
    {
        return $this->belongsToMany(Discipline::class);
    }

    public function folders()
    {
        return $this->hasMany(Folder::class);
    }

    public function rfis()
    {
        return $this->hasMany(Rfi::class);
    }

    public function emailLogs()
    {
        return $this->hasMany(EmailLog::class);
    }

    public function approvalWorkflows()
    {
        return $this->hasMany(ApprovalWorkflow::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function getComplianceStatusAttribute()
    {
        // For now, return green if there is at least one document, else red/yellow
        $count = $this->documents()->count();
        if ($count == 0) return 'red';
        if ($count < 5) return 'yellow';
        return 'green';
    }
}
