<?php

namespace App\Events;

use App\Models\Cm;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CmProvisioningComplete
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The CM instance.
     *
     * @var \App\Models\Cm
     */
    public $cm;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Cm $cm)
    {
        $this->cm = $cm;
    }
}
