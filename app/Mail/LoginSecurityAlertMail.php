<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginSecurityAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public array $loginDetails;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, array $loginDetails = [])
    {
        $this->user = $user;
        $this->loginDetails = $loginDetails;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $appName = config('app.name', 'Saint Paul Institute E-LMS');
        return new Envelope(
            from: new Address(
                config('mail.from.address', 'security@spilms.tech'),
                config('mail.from.name', 'SPI E-LMS Security')
            ),
            subject: '🛡️ ការជូនដំណឹងសុវត្ថិភាព៖ ការចូលប្រើប្រាស់គណនីថ្មី | Security Alert: New Login to SPI LMS',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.login_security_alert',
            with: [
                'user' => $this->user,
                'userName' => $this->user->name_kh ? "{$this->user->name_kh} ({$this->user->name})" : $this->user->name,
                'email' => $this->user->email,
                'role' => ucfirst($this->user->role ?? 'Student'),
                'ip' => $this->loginDetails['ip'] ?? 'Unknown IP',
                'device' => $this->loginDetails['device'] ?? 'Desktop',
                'browser' => $this->loginDetails['browser'] ?? 'Browser',
                'time' => $this->loginDetails['time'] ?? now()->setTimezone('Asia/Phnom_Penh')->format('d-M-Y h:i A'),
                'location' => $this->loginDetails['location'] ?? 'Cambodia',
                'secureAccountUrl' => url('/forgot-password?email=' . urlencode($this->user->email)),
                'loginUrl' => url('/login'),
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
