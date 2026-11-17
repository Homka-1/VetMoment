<?php

namespace App\Notifications;

use App\Enums\ApplicationType;
use App\Enums\Tone;
use App\Models\AdoptionApplication;

/** Приюту: новая заявка «Забрать домой» / «Взять под опеку». */
class ApplicationSubmitted extends AppNotification
{
    public function __construct(AdoptionApplication $application)
    {
        $application->loadMissing('pet');
        $pet = $application->pet->name;

        $this->title = $application->type === ApplicationType::Adoption
            ? "{$pet}: новая заявка «Забрать домой»"
            : "{$pet}: новая заявка «Взять под опеку»";
        $this->body = $application->contact_name.', '.$application->city.'. Свяжитесь с человеком и отметьте решение в заявке.';
        $this->url = route('shelter.applications.show', $application);
        $this->actionText = 'Открыть заявку';
        $this->icon = 'mail';
        $this->tone = Tone::Warning;

        parent::__construct();
    }

    public function kind(): string
    {
        return 'application.submitted';
    }
}
