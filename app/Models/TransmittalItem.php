<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransmittalItem extends Model
{
    protected $fillable = ['transmittal_id', 'file_revision_id'];

    public function transmittal()
    {
        return $this->belongsTo(Transmittal::class);
    }

    public function revision()
    {
        return $this->belongsTo(FileRevision::class, 'file_revision_id');
    }
}
