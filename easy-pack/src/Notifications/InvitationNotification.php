<?php

namespace EasyPack\Notifications;

use EasyPack\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The invitation instance.
     */
    protected Invitation $invitation;

    /**
     * Create a new notification instance.
     */
    public function __construct(Invitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name');
        $inviterName = $this->invitation->inviter?->name ?? 'An administrator';
        $expiresAt = $this->invitation->expires_at->format('F j, Y \a\t g:i A');

        $mail = (new MailMessage)
            ->subject("You're invited to join {$appName}")
            ->greeting("Hello!")
            ->line("{$inviterName} has invited you to join {$appName}.");

        if (isset($this->invitation->data['message']) && $this->invitation->data['message']) {
            $mail->line("Personal message: \"{$this->invitation->data['message']}\"");
        }

        if (isset($this->invitation->data['role']) && $this->invitation->data['role']) {
            $mail->line("You will be assigned the role: **{$this->invitation->data['role']}**");
        }

        $mail->action('Accept Invitation', $this->getInvitationUrl())
            ->line("This invitation will expire on {$expiresAt}.")
            ->line('If you did not expect this invitation, no action is required.');

        return $mail;
    }

    /**
     * Get the invitation URL.
     */
    protected function getInvitationUrl(): string
    {
        return route('invitations.join', ['code' => $this->invitation->token]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'email' => $this->invitation->email,
            'expires_at' => $this->invitation->expires_at->toISOString(),
        ];
    }
}
