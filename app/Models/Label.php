<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Label extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'printer_type', 'print_command', 'ftp_hostname', 'ftp_username', 'ftp_password', 'template', 'file_extension'];
    protected $hidden = ['ftp_password'];
}
