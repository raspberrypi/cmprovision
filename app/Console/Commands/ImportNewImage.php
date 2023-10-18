<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Image;
use App\Models\Project;
use App\Jobs\ComputeSHA256;

class ImportNewImage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:image {filepath}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $filepath = $this->argument('filepath');
        $extension =  pathinfo($filepath, \PATHINFO_EXTENSION);
        // validate image file type
        if ($extension == '' || !in_array($extension, ['gz','bz2','xz'])) {
             echo "Only .gz, .bz2 and .xz images are supported";
             return -1;
        }

        $i = new Image;
        $i->filename = $filepath;
        $i->filename_extension = $extension;
        $i->sha256 = '';
        do
        {
            $i->filename_on_server = Str::random(40).".".$i->filename_extension;
        } while ( file_exists($i->imagepath()) );

        rename($filepath, public_path("uploads").'/'.$i->filename_on_server);
        $i->save();

        /* Queue SHA256 calculation job */
        ComputeSHA256::dispatch($i);

        // find the active project and update the image reference
        $project = Project::getActive();
        $project->image_id = $i->id;
        $project->save();
    }
}
