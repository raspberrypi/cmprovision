<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Label;

class Labels extends Component
{
    public $isOpen = false;
    public $labels;
    public $labelid, $name, $printer_type, $print_command;
    public $ftp_hostname, $ftp_username, $ftp_password, $file_extension, $template;
    public $dummypassword = "****************";

    protected $rules = [
        'name' => 'required|max:255',
        'printer_type' => 'in:ftp,command',
        'ftp_hostname' => 'nullable|max:255|required_if:printer_type,ftp',
        'ftp_username' => 'nullable|max:255|required_if:printer_type,ftp',
        'ftp_password' => 'nullable|max:255|required_if:printer_type,ftp',
        'print_command' => 'nullable|max:255|required_if:printer_type,command',
        'file_extension' => 'required|max:32|regex:/^[a-zA-Z0-9]+$/',
        'template' => 'required'
    ];

    public function render()
    {
        $this->labels = Label::orderBy('name')->get();
        return view('livewire.labels');
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
        Label::destroy($id);
        session()->flash('message', 'Label deleted.');
    }

    public function create()
    {
        $this->name = $this->labelid = '';
        $this->printer_type = 'command';
        $this->ftp_hostname = $this->ftp_username = $this->ftp_password = '';
        $this->template = '';
        $this->file_extension = 'jscript';
        $this->print_command = '/usr/bin/lpr -o raw $file 2>&1';
        $this->openModal();
    }

    public function edit($id)
    {
        $l = Label::findOrFail($id);
        $this->labelid = $id;
        $this->name = $l->name;
        $this->printer_type = $l->printer_type;
        $this->ftp_hostname = $l->ftp_hostname;
        $this->ftp_username = $l->ftp_username;
        $this->ftp_password = empty($l->ftp_password) ? "" : $this->dummypassword;
        $this->template = $l->template;
        $this->file_extension = $l->file_extension;
        $this->print_command = $l->print_command;
        $this->openModal();
    }

    public function store()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'printer_type' => $this->printer_type,
            'ftp_hostname' => $this->ftp_hostname,
            'ftp_username' => $this->ftp_username,
            'template' => $this->template,
            'file_extension' => $this->file_extension,
            'print_command' => $this->print_command
        ];
        if ($this->ftp_password != $this->dummypassword)
        {
            $data['ftp_password'] = $this->ftp_password;
        }

        Label::updateOrCreate(['id' => $this->labelid], $data);

        $this->closeModal();
    }

    public function cancel()
    {
        $this->closeModal();
    }

    public function printTestLabel()
    {
        $serial = '1000000012345678';
        $label = str_replace('$mac', '11:22:33:44:55:66', $this->template);
        $label = str_replace('$serial', $serial, $label);
        $label = str_replace('$provisionboard', '000 (0)', $label);
        $tmpfile = tempnam(sys_get_temp_dir(), "label-");
        $ftp_password = $this->ftp_password;
        if ($ftp_password == $this->dummypassword)
        {
            $l = Label::findOrFail($this->labelid);
            $ftp_password = $l->ftp_password;
        }

        try
        {
            if (!@file_put_contents($tmpfile, $label))
                throw new \Exception("Error creating temporary file for label '$tmpfile'");

            if ($this->printer_type == 'ftp')
            {
                $ftp = @ftp_connect($this->ftp_hostname);
                if (!$ftp)
                    throw new \Exception("Error connecting to printer's FTP server ".$this->ftp_hostname);
                if (!@ftp_login($ftp, $this->ftp_username, $ftp_password))
                    throw new \Exception("Error logging in to printer's FTP server. Check username and password");
                @ftp_pasv($ftp, true);
                if (!@ftp_put($ftp, "label-$serial.".$this->file_extension, $tmpfile))
                    throw new \Exception("Error uploading file to printer's FTP server");
                @ftp_close($ftp);
            }
            else if ($this->printer_type == 'command')
            {
                $cmd = str_replace('$file', escapeshellarg($tmpfile), $this->print_command);
                $output = $retcode = null;
                if (@exec($cmd, $output, $retcode) === false)
                    throw new \Exception("Error executing '$cmd'");
                if ($retcode)
                    throw new \Exception("Executing '$cmd' returned exit code $retcode. Program output:\n".implode("\n", $output));
            }

            session()->flash('message', 'Printed test label');
        }
        catch (\Exception $e)
        {
            session()->flash('message', $e->getMessage() );
        }
        @unlink($tmpfile);
    }    
}
