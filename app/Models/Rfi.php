<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rfi extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'creator_id',
        'assigned_to_id',
        'number',
        'subject',
        'description',
        'status',
        'priority',
        'due_date'
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function responses()
    {
        return $this->hasMany(RfiResponse::class);
    }

    public function attachments()
    {
        return $this->hasMany(RfiAttachment::class);
    }
}
