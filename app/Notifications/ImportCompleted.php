<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImportCompleted extends Notification
{
    use Queueable;

    protected $message;
    protected $errorDetails;

    /**
     * Create a new notification instance.
     *
     * @param string $message
     * @param array $errorDetails
     * @return void
     */
    public function __construct($message, $errorDetails = [])
    {
        $this->message = $message;
        $this->errorDetails = $errorDetails;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mailMessage = (new MailMessage)
            ->subject('Import Data Revenue Selesai')
            ->line('Proses import data revenue telah selesai.')
            ->line($this->message);

        // Tambahkan detail error jika ada
        if (!empty($this->errorDetails)) {
            $mailMessage->line('Detail error:');
            foreach ($this->errorDetails as $error) {
                $mailMessage->line("- $error");
            }
        }

        $mailMessage->action('Lihat Data Revenue', url('/revenue'));

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'title' => 'Import Data Revenue Selesai',
            'message' => $this->message,
            'error_details' => $this->errorDetails,
            'timestamp' => now()->toDateTimeString()
        ];
    }
}
