<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Уведомление в кабинете: заголовок, текст, ссылка, иконка и цвет.
 * Хранится в базе; страница «Уведомления» показывает его карточкой.
 */
abstract class CabinetNotification extends Notification
{
    abstract protected function title(): string;

    abstract protected function body(): string;

    abstract protected function url(): string;

    /**
     * Иконка Flux (heroicons).
     */
    protected function icon(): string
    {
        return 'bell';
    }

    /**
     * Цвет Tailwind: sky, amber, red, green, zinc…
     */
    protected function color(): string
    {
        return 'sky';
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{title: string, body: string, url: string, icon: string, color: string}
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->body(),
            'url' => $this->url(),
            'icon' => $this->icon(),
            'color' => $this->color(),
        ];
    }
}
