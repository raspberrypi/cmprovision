<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cm extends Model
{
    use HasFactory;

    protected $fillable = [
        'serial','mac','model','memory_in_gb','storage','csd','cid','firmware',
        'image_filename', 'image_sha256', 'pre_script_output', 'post_script_output', 'script_return_code',
        'temp1', 'temp2', 'provisioning_board', 'provisioning_started_at', 'provisioning_complete_at', 'project_id'
    ];
    
    protected $casts = [
        'provisioning_started_at' => 'datetime',
        'provisioning_complete_at' => 'datetime'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
