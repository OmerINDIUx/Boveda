<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'description'];

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function rfis()
    {
        return $this->hasMany(Rfi::class);
    }

    public function emailLogs()
    {
        return $this->hasMany(EmailLog::class);
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
