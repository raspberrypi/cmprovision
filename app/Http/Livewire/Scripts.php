<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Script;

class Scripts extends Component
{
    public $isOpen = false;
    public $scripts;
    public $scriptid, $name, $script_type, $bg, $priority, $script;

    protected $rules = [
        'name' => 'required|max:255',
        'script_type' => 'in:preinstall,postinstall',
        'bg' => 'required|boolean',
        'priority' => 'required|numeric',
        'script' => 'required'
    ];

    public function render()
    {
        $this->scripts = Script::orderBy('name')->get();
        return view('livewire.scripts');
    }

    public function openModal()
    {
        $this->resetErrorBag();
        $this->resetValidation();        
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }
    
    public function delete($id)
    {
        $projects = Script::findOrFail($id)->projects;
        if (count($projects))
        {
            $plist = '';
            foreach ($projects as $project)
            {
                $plist .= "'".$project->name."' ";
            }
            session()->flash('message', 'Cannot delete. Script is being used by projects: '.$plist);
            return;
        }

        Script::destroy($id);
        session()->flash('message', 'Script deleted.');
    }

    public function create()
    {
        $this->name = $this->scriptid = '';
        $this->script_type = 'postinstall';
        $this->bg = false;
        $this->priority = 100;
        $this->script = "#!/bin/sh\nset -e\n\n";
        $this->openModal();
    }

    public function edit($id)
    {
        $s = Script::findOrFail($id);
        $this->scriptid = $id;
        $this->name = $s->name;
        $this->script_type = $s->script_type;
        $this->bg = $s->bg;
        $this->priority = $s->priority;
        $this->script = $s->script;
        $this->openModal();
    }

    public function store()
    {
        $this->validate();

        Script::updateOrCreate(['id' => $this->scriptid], [
            'name' => $this->name,
            'script_type' => $this->script_type,
            'bg' => $this->bg,
            'priority' => $this->priority,
            'script' => str_replace("\r", "", $this->script)
        ]);

        $this->closeModal();
    }

    public function cancel()
    {
        $this->closeModal();
    }
}
