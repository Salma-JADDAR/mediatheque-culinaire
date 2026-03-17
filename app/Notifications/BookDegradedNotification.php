<?php

namespace App\Notifications;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class BookDegradedNotification extends Notification
{
    use Queueable;

    protected $book;

    public function __construct(Book $book)
    {
        $this->book = $book;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Livre marqué comme dégradé - ' . $this->book->title)
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Le livre suivant a été marqué comme dégradé :')
            ->line('**Titre :** ' . $this->book->title)
            ->line('**Auteur :** ' . $this->book->author)
            ->line('**État :** ' . $this->book->condition)
            ->line('**Catégorie :** ' . $this->book->category->name)
            ->action('Voir le livre', url('/api/books/' . $this->book->id))
            ->line('Merci de prévoir une réparation ou un remplacement.');
    }

    public function toArray($notifiable)
    {
        return [
            'book_id' => $this->book->id,
            'book_title' => $this->book->title,
            'book_author' => $this->book->author,
            'condition' => $this->book->condition,
            'message' => 'Le livre "' . $this->book->title . '" a été marqué comme dégradé'
        ];
    }
}