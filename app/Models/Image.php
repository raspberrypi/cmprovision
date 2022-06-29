<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $casts = ['uncompressed_size' => 'integer'];

    function imagepath()
    {
        return public_path('uploads/'.$this->filename_on_server);
    }

    function filesize()
    {
        return filesize( $this->imagepath() );
    }

    function delete()
    {
        // Delete image from filesystem
        unlink( $this->imagepath() );

        // Delete from database
        parent::delete();
    }
}
