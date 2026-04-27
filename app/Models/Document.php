<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'project_id',
        'discipline_id',
        'folder_id',
        'document_number',
        'title',
        'status',
        'is_locked',
        'approval_status',
        'confidentiality_level'
    ];

    public function scopeVisibleTo($query, User $user)
    {
        $clearanceValues = [
            'standard' => 0,
            'internal' => 1,
            'manager' => 2,
            'admin' => 3
        ];
        
        $confidentialityValues = [
            'public' => 0,
            'internal' => 1,
            'restricted' => 2,
            'confidential' => 3
        ];

        $userLevel = $clearanceValues[$user->clearance_level] ?? 0;

        // Collect all levels the user is allowed to see
        $allowedLevels = array_keys(array_filter($confidentialityValues, function($val) use ($userLevel) {
            return $userLevel >= $val;
        }));

        // Allow if the user has clearance, OR if the user is the creator of the latest revision (for simplicity).
        // Since document doesn't track creator_id directly, we filter by clearance level.
        return $query->whereIn('confidentiality_level', $allowedLevels);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function discipline()
    {
        return $this->belongsTo(Discipline::class);
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class);
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
