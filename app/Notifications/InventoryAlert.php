<?php

namespace App\Notifications;

use AllowDynamicProperties;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

#[AllowDynamicProperties]
class InventoryAlert extends Notification
{
    use Queueable;
    /**
     * @var
     */
    private $params;

    /**
     * Create a new notification instance.
     *
     * @param $params
     */
    public function __construct($params, $threshold)
    {
        $this->items = $params;
        $this->threshold = $threshold;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     */
    public function via()
    {
       return (!empty($this->items) && $this->threshold !== null) ? ['mail'] : [];

    }

    /**
     * Get the mail representation of the notification.
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail()
    {
        $message = (new MailMessage)->markdown(
            'notifications.markdown.report-low-inventory',
            [
                'items'  => $this->items,
                'threshold'  => $this->threshold,
            ]
        )
            ->subject(trans('mail.Low_Inventory_Report'));

        return $message;
    }
}
