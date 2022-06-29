<?php
/*
 * Handling file upload with normal controller. As livewire still has some issues with large files
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Image;
use App\Jobs\ComputeSHA256;

class AddImageController extends Controller
{
    public function store(Request $req)
    {
        @set_time_limit(86400);
        @ignore_user_abort(true);

        Validator::extend('file_extension',
        function($attribute, $value, $parameters, $validator) {
            if (!$value instanceof \Illuminate\Http\UploadedFile) {
                return false;
            }

            $extension = $value->getClientOriginalExtension();
            return $extension != '' && in_array($extension, $parameters);
        }, "Only .gz, .bz2 and .xz images are supported");

        $data = $req->validate([
            'image' => 'required|file|file_extension:gz,bz2,xz',
        ]);

        $i = new Image;
        $i->filename = $data['image']->getClientOriginalName();
        $i->filename_extension = $data['image']->getClientOriginalExtension();
        //$i->sha256 = hash_file("sha256", $data['image']->getPathname());
        $i->sha256 = '';
        do
        {
            $i->filename_on_server = Str::random(40).".".$i->filename_extension;
        } while ( file_exists($i->imagepath()) );

        $data['image']->move(public_path("uploads"), $i->filename_on_server);
        $i->save();

        /* Queue SHA256 calculation job */
        ComputeSHA256::dispatch($i);

        if ($req->wantsJson())
        {
            return $i;
        }
        else
        {
            session()->flash('message', 'Image added.');
            return redirect()->route('images');
        }
    }
}
