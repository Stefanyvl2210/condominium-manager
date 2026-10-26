<?php

namespace App\Notifications;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Support\Money;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReviewed extends Notification
{
    public function __construct(public Payment $payment) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->isApproved() ? 'Your payment was approved' : 'Your payment was rejected')
            ->greeting("Hello {$notifiable->name},")
            ->line($this->summary());

        if (! $this->isApproved()) {
            $message->line("Reason: {$this->payment->rejection_reason}");
        }

        return $message->action('View my payments', route('filament.portal.resources.payments.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        $notification = FilamentNotification::make()
            ->title($this->isApproved() ? 'Payment approved' : 'Payment rejected')
            ->body($this->isApproved() ? $this->summary() : $this->summary().' Reason: '.$this->payment->rejection_reason);

        return ($this->isApproved() ? $notification->success() : $notification->danger())->getDatabaseMessage();
    }

    private function isApproved(): bool
    {
        return $this->payment->status === PaymentStatus::Approved;
    }

    private function summary(): string
    {
        return sprintf(
            'Your payment of %s (reference %s) for unit %s was %s.',
            Money::format($this->payment->amount, $this->payment->currency),
            $this->payment->reference ?? 'n/a',
            $this->payment->unit->label,
            $this->isApproved() ? 'approved and applied to your balance' : 'rejected',
        );
    }
}
