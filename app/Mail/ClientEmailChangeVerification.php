<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientEmailChangeVerification extends Mailable
{
    use Queueable, SerializesModels;

    public $client;
    public $otp;
    public $newEmail;
    public $oldEmail;

    /**
     * Create a new message instance.
     */
    public function __construct(Client $client, string $otp, string $newEmail, string $oldEmail)
    {
        $this->client = $client;
        $this->otp = $otp;
        $this->newEmail = $newEmail;
        $this->oldEmail = $oldEmail;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Email Change Verification - EMOH Real Estate',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.client-email-change-verification',
            with: [
                'client' => $this->client,
                'otp' => $this->otp,
                'newEmail' => $this->newEmail,
                'oldEmail' => $this->oldEmail,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
