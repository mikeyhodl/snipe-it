<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class BulkAssetCheckoutMail extends Mailable
{
    use Queueable, SerializesModels;

    public bool $requires_acceptance;

    public function __construct(
        public Collection $assets,
        public Model $target,
        public User $admin,
        public string $checkout_at,
        public string $expected_checkin,
    ) {
        $this->requires_acceptance = $this->requiresAcceptance();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->getSubject(),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.markdown.bulk-asset-checkout-mail',
            with: [
                'introduction' => $this->getIntroduction(),
                'requires_acceptance' => $this->requiresAcceptance(),
                'acceptance_url' => $this->acceptanceUrl(),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    private function getSubject(): string
    {
        if ($this->assets->count() > 1) {
            // @todo: translate
            return 'Assets checked out';
        }

        return trans('mail.Asset_Checkout_Notification', ['tag' => $this->assets->first()->asset_tag]);
    }

    private function getIntroduction(): string
    {
        if ($this->assets->count() > 1) {
            // @todo: translate
            return 'Assets have been checked out to you.';
        }

        // @todo: translate
        return 'An asset has been checked out to you.';
    }

    private function requiresAcceptance(): bool
    {
        return (bool) $this->assets->reduce(
            fn($count, $asset) => $count + $asset->requireAcceptance()
        );
    }

    private function acceptanceUrl()
    {
        if ($this->assets->count() > 1) {
            return route('account.accept');
        }

        return route('account.accept.item', $this->assets->first());
    }
}
