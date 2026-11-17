<?php

namespace App\Notifications;

use App\Enums\Tone;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Общая основа уведомлений VetMoment (этап 12): одно сообщение — два канала.
 *
 * - database — запись для страницы /notifications и колокольчика. Пишется сразу (соединение sync),
 *   чтобы счётчик не зависел от того, запущен ли воркер очереди;
 * - mail — письмо через очередь (воркер `queue` в Docker), если у получателя есть адрес.
 *
 * Текст собирается в момент отправки и хранится в data — история не меняется задним числом.
 * Отправка — только после коммита транзакции (afterCommit), чтобы не сообщать об откаченном.
 */
abstract class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** Заголовок: тема письма и первая строка в списке. */
    protected string $title = '';

    /** Короткое пояснение (одно-два предложения). */
    protected string $body = '';

    /** Куда ведёт уведомление. */
    protected string $url = '';

    /** Подпись кнопки в письме. */
    protected string $actionText = 'Открыть';

    /** Иконка из спрайта (resources/svg/icons.svg). */
    protected string $icon = 'bell';

    protected Tone $tone = Tone::Info;

    /** Имя для приветствия в письме, если получатель — не пользователь (гость-жертвователь). */
    protected ?string $greetingName = null;

    /** @var list<string> дополнительные строки письма (причина отказа и т.п.) */
    protected array $details = [];

    /**
     * Наследник заполняет поля в конструкторе и вызывает parent::__construct():
     * в очередь уходят готовые строки, а не модели — письмо не зависит от того, что стало с записью потом.
     */
    public function __construct()
    {
        $this->afterCommit();
    }

    /** «walk.confirmed» — тип в notifications.type, для фильтров и тестов. */
    abstract public function kind(): string;

    public function title(): string
    {
        return $this->title;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function url(): string
    {
        return $this->url;
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable instanceof User) {
            $channels[] = 'database';
        }

        if (filled($notifiable->routeNotificationFor('mail', $this))) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    /** Тип в колонке notifications.type — короткий ключ, а не имя класса. */
    public function databaseType(object $notifiable): string
    {
        return $this->kind();
    }

    /**
     * @return array{kind: string, title: string, body: string, url: string, icon: string, tone: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->kind(),
            'title' => $this->title(),
            'body' => $this->body(),
            'url' => $this->url(),
            'icon' => $this->icon,
            'tone' => $this->tone->value,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $notifiable instanceof AnonymousNotifiable ? $this->greetingName : ($notifiable->name ?? null);

        $mail = (new MailMessage)
            ->subject($this->title())
            ->greeting($name ? "Здравствуйте, {$name}!" : 'Здравствуйте!')
            ->line($this->body());

        foreach ($this->details as $line) {
            $mail->line($line);
        }

        return $mail
            ->action($this->actionText, $this->url())
            ->salutation("С заботой о хвостатых,\nкоманда ".config('app.name'));
    }
}
