<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Cm;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class Cms extends Component
{
    public $CMs;
    public $isOpen = false;
    public $cm;
    public $projectId = -1;
    public $projects;

    public function render()
    {
        if ($this->projectId == -1)
        {
            $this->projectId = Project::getActiveId();
        }

        if ($this->projectId)
        {
            $this->CMs = Cm::where('project_id', $this->projectId)->orderBy('id')->get();
        }
        else
        {
            $this->CMs = Cm::orderBy('id')->get();
        }
        $this->projects = Project::withCount('cms')->orderBy('name')->get();

        return view('livewire.cms');
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    public function edit($id)
    {
        $this->cm = Cm::findOrFail($id);
        $this->openModal();
    }

    public function delete($id)
    {
        Cm::destroy($id);
        session()->flash('message', 'Cm deleted.');
    }

    public function cancel()
    {
        $this->closeModal();
    } 

    public function exportCSV()
    {
        return response()->streamDownload(function () {
            $fd = fopen('php://output', 'w'); 

            if ( count($this->CMs) )
            {
                fputcsv($fd, array_keys($this->CMs[0]->getAttributes() ));

                foreach ($this->CMs as $cm)
                {
                    fputcsv($fd, $cm->getAttributes());
                }
            }
            fclose($fd);

        }, 'export-cm-'.date('Ymd').'.csv');        
    }
}
