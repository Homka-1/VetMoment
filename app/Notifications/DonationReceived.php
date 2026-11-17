<?php

namespace App\Notifications;

use App\Enums\Tone;
use App\Models\Donation;
use App\Support\LocalTime;
use App\Support\Money;

/** Жертвователю: платёж прошёл — спасибо и квитанция (письмо приходит и гостю на указанный email). */
class DonationReceived extends AppNotification
{
    public function __construct(Donation $donation)
    {
        $donation->loadMissing('campaign', 'shelter');
        $amount = Money::format($donation->amount);

        $this->title = 'Спасибо за пожертвование';
        $this->body = "Платёж на {$amount} прошёл: {$donation->purposeLabel()}. Приют «{$donation->shelter->name}» опубликует отчёт о том, на что ушли деньги.";
        $this->details = [
            'Квитанция № '.$donation->uuid.' от '.LocalTime::dateTime($donation->paid_at ?? now()).'.',
        ];
        $this->url = route('donations.show', $donation);
        $this->actionText = 'Открыть квитанцию';
        $this->icon = 'heart';
        $this->tone = Tone::Tag;
        $this->greetingName = $donation->is_anonymous ? null : $donation->donor_name;

        parent::__construct();
    }

    public function kind(): string
    {
        return 'donation.succeeded';
    }
}
