<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Image;
use \Illuminate\Http\UploadedFile;

class Images extends Component
{
    use \Livewire\WithFileUploads;

    public $images, $maxfilesize, $freediskspace;
    public $isOpen = false;
    public $os32bit = false;

    public function render()
    {
        $this->images = Image::orderBy('filename')->orderBy('id')->get();
        $this->maxfilesize = UploadedFile::getMaxFilesize();
        $this->freediskspace = min( disk_free_space("/tmp"), disk_free_space(public_path("uploads")));
        $this->os32bit = (PHP_INT_MAX == 2147483647);

        return view('livewire.images');
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }
    
    public function delete($id)
    {
        Image::destroy($id);
        session()->flash('message', 'Image deleted.');
    }

    public function create()
    {
        $this->openModal();
    }

    public function cancel()
    {
        $this->closeModal();
    }
}
