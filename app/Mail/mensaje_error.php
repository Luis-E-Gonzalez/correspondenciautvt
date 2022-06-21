<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class mensaje_error extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public $subject = "Atención, al parecer tiene un error en la actividad.";

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('avisos@utvtol.edu.mx')
            ->view('mails.error');
    }
}
