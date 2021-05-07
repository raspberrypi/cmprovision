<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Script extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'script_type', 'bg', 'priority', 'script'];
    protected $casts = ['bg' => 'boolean', 'priority' => 'integer'];

    function projects()
    {
        return $this->belongsToMany(Project::class);
    }    
}
