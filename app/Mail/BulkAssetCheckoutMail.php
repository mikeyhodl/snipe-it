<?php

namespace App\Mail;

use App\Models\Asset;
use App\Models\CustomField;
use App\Models\Location;
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
        public string $note,
    ) {
        $this->requires_acceptance = $this->requiresAcceptance();

        $this->loadCustomFieldsOnAssets();
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
                'eula' => $this->getEula(),
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
        if ($this->target instanceof Location && $this->assets->count() > 1) {
            // @todo: translate
            return "Assets have been checked out to {$this->target->name}.";
        }

        if ($this->assets->count() > 1) {
            // @todo: translate
            return 'Assets have been checked out to you.';
        }

        // @todo: translate
        return 'An asset has been checked out to you.';
    }

    private function acceptanceUrl()
    {
        if ($this->assets->count() > 1) {
            return route('account.accept');
        }

        return route('account.accept.item', $this->assets->first());
    }

    private function getEula()
    {
        // if assets do not have the same category then return early...
        $categories = $this->assets->pluck('model.category.id')->unique();

        if ($categories->count() > 1) {
            return;
        }

        // if assets do have the same category then return the shared EULA
        if ($categories->count() === 1) {
            return $this->assets->first()->getEula();
        }

        // @todo: if the categories use the default eula then return that
    }

    private function loadCustomFieldsOnAssets(): void
    {
        $this->assets = $this->assets->map(function (Asset $asset) {
            $fields = $asset->model?->fieldset?->fields->filter(function (CustomField $field) {
                return $field->show_in_email && !$field->field_encrypted;
            });

            $asset->setRelation('fields', $fields);

            return $asset;
        });
    }

    private function requiresAcceptance(): bool
    {
        return (bool) $this->assets->reduce(
            fn($count, $asset) => $count + $asset->requireAcceptance()
        );
    }
}
