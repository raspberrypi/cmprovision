<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cmlog extends Model
{
    use HasFactory;
    protected $fillable = ['cm','board','loglevel','msg'];
}
