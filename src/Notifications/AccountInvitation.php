<?php

namespace Intranet\Modules\UserImport\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Einladungs-Mail für frisch importierte Benutzer. Enthält einen Link zum
 * Festlegen des eigenen Passworts. Technisch benutzt der Link denselben
 * Mechanismus wie "Passwort vergessen" (Laravels password_reset_tokens).
 */
class AccountInvitation extends Notification
{
    use Queueable;

    public function __construct(public string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Gleiche Link-Struktur wie Laravels Standard-Passwort-Reset.
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Willkommen im Intranet – bitte Passwort festlegen')
            ->greeting('Hallo '.$notifiable->name.'!')
            ->line('Für dich wurde ein Zugang zum Intranet angelegt.')
            ->line('Bitte lege über den folgenden Button dein persönliches Passwort fest:')
            ->action('Passwort jetzt festlegen', $url)
            ->line('Aus Sicherheitsgründen ist dieser Link nur begrenzt gültig. '
                .'Falls er abgelaufen ist, nutze auf der Anmeldeseite "Passwort vergessen".')
            ->salutation('Viele Grüße vom Intranet-Team');
    }
}
