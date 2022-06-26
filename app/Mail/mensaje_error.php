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
    public $user;

    public $subject = "Atención, al parecer tiene un error en una de sus actividades.";

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $user)
    {
        $this->data = $data;
        $this->user = $user;

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
