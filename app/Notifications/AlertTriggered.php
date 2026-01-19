<?php

namespace App\Notifications;

use App\Models\Alert;
use App\Models\ExchangeRate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlertTriggered extends Notification implements ShouldQueue
{
    use Queueable;

    protected $alert;
    protected $rate;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Alert $alert, ExchangeRate $rate)
    {
        $this->alert = $alert;
        $this->rate = $rate;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $conditionText = $this->alert->condition == 'above' ? 'subido por encima de' : 'bajado por debajo de';
        
        return (new MailMessage)
                    ->subject('Alerta de Tipo de Cambio - ' . $this->alert->exchangeSource->name)
                    ->line('Hola,')
                    ->line("El tipo de cambio en {$this->alert->exchangeSource->name} ha {$conditionText} tu precio objetivo de {$this->alert->target_price}.")
                    ->line("Precio de Compra Actual: {$this->rate->buy_price}")
                    ->line("Precio de Venta Actual: {$this->rate->sell_price}")
                    ->action('Ver Alertas', url('/currency-alert'))
                    ->line('Gracias por usar nuestra utilidad de alertas.');
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
            //
        ];
    }
}
