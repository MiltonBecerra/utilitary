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
        // Limpiar título de entidades HTML
        $title = $this->alert->title ?: 'Producto';
        if ($title) {
            $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $title = stripslashes($title);
        }
        
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

