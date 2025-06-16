<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GenericMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subjectLine;
    public $bodyContent;

    public function __construct($subject, $body)
    {
        $this->subjectLine = $subject;
        $this->bodyContent = $body;
    }

    public function build()
    {
        return $this->subject($this->subjectLine)
            ->view('emails.generic')
            ->with(['body' => $this->bodyContent]);
    }
}
