<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployerRoleRequest extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $requestMessage;

    /**
     * Create a new message instance.
     *
     * @param \App\Models\User $user
     * @param string $requestMessage
     * @return void
     */
    public function __construct(User $user, $requestMessage)
    {
        $this->user = $user;
        $this->requestMessage = $requestMessage;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            from: $this->user->email, // The "from" address is the user who sent it
            subject: 'New Employer Role Request',
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'emails.employer-request',
            with: [
                'user' => $this->user,
                'requestMessage' => $this->requestMessage,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
