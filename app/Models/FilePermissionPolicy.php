<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FilePermissionPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'role_name',
        'permissions',
        'scope',
        'conditions',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'permissions' => 'array',
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
