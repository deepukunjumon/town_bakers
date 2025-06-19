<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;

class StockSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public $branchName;
    public $date;
    public $filePath;
    public $fileName;

    /**
     * Create a new message instance.
     */
    public function __construct(string $branchName, string $date, string $filePath, string $fileName)
    {
        $this->branchName = $branchName;
        $this->date = $date;
        $this->filePath = $filePath;
        $this->fileName = $fileName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Stock Summary - {$this->branchName} ({$this->date})",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.stock.summary',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->filePath)
                ->as($this->fileName)
        ];
    }
}
