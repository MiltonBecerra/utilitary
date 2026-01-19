<?php

namespace App\Notifications;

use App\Models\OfferAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OfferAlertTriggered extends Notification
{
    use Queueable;

    public function __construct(
        protected OfferAlert $alert,
        protected float $currentPrice,
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $title = $this->alert->title ?: 'Producto';
        $store = $this->alert->store ?: 'tienda';
        $targetText = $this->alert->target_price ? ('S/ ' . number_format((float) $this->alert->target_price, 2)) : 'cualquier baja';

        return (new MailMessage)
            ->subject("Alerta de oferta: {$title}")
            ->line("Tienda: {$store}")
            ->line('Precio actual: S/ ' . number_format($this->currentPrice, 2))
            ->line("Condición: {$targetText}")
            ->action('Ver producto', $this->alert->url)
            ->line('Gracias por usar nuestras alertas.');
    }
}

