<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Models\User;

class RequestAccepted extends Notification
{
    use Queueable;

    public $helper;
    public $animalRequest;

    public function __construct(User $helper, $animalRequest)
    {
        $this->helper = $helper;
        $this->animalRequest = $animalRequest;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Пользователь {$this->helper->login} готов помочь с заявкой '{$this->animalRequest->title}'.",
            'phone' => $this->helper->phone,
            'helper_id' => $this->helper->id,
            'request_id' => $this->animalRequest->id,
        ];
    }
}
