<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\User;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceIssued extends Notification
{
    public function __construct(public Invoice $invoice) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $invoice = $this->invoice;

        return (new MailMessage)
            ->subject("Your {$invoice->billingPeriod->name} statement for unit {$invoice->unit->label}")
            ->greeting("Hello {$notifiable->name},")
            ->line("The {$invoice->billingPeriod->name} statement for unit {$invoice->unit->label} is ready.")
            ->line('Amount due: '.Money::format($invoice->balance))
            ->line('Due date: '.$invoice->due_on->toFormattedDateString())
            ->action('View statement', route('filament.portal.resources.invoices.view', $invoice))
            ->line('If you already paid, you can ignore this message.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return FilamentNotification::make()
            ->title('New statement issued')
            ->body(sprintf(
                '%s for unit %s: %s due %s.',
                $this->invoice->billingPeriod->name,
                $this->invoice->unit->label,
                Money::format($this->invoice->balance),
                $this->invoice->due_on->toFormattedDateString(),
            ))
            ->icon('heroicon-o-document-text')
            ->actions([
                Action::make('view')->url(route('filament.portal.resources.invoices.view', $this->invoice)),
            ])
            ->getDatabaseMessage();
    }
}
