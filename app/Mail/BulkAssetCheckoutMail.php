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
                'requires_acceptance' => $this->requires_acceptance,
                'requires_acceptance_wording' => $this->getRequiresAcceptanceWording(),
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
            return ucfirst(trans('general.assets_checked_out_count'));
        }

        return trans('mail.Asset_Checkout_Notification', ['tag' => $this->assets->first()->asset_tag]);
    }

    private function getIntroduction(): string
    {
        if ($this->target instanceof Location) {
            return trans_choice('mail.new_item_checked_location', $this->assets->count(), ['location' => $this->target->name]);
        }

        return trans_choice('mail.new_item_checked', $this->assets->count());
    }

    private function acceptanceUrl()
    {
        if ($this->assets->count() > 1) {
            return route('account.accept');
        }

        return route('account.accept.item', $this->assets->first());
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

    private function getRequiresAcceptanceWording(): array
    {
        if (!$this->requiresAcceptance()) {
            return [];
        }

        if ($this->assets->count() > 1) {
            return [
                // todo: translate
                trans_choice('mail.items_checked_out_require_acceptance', $this->assets->count()),
                "**[✔ Click here to review the terms of use and accept the items]({$this->acceptanceUrl()})**",
            ];
        }

        return [
            // todo: translate
            trans_choice('mail.items_checked_out_require_acceptance', $this->assets->count()),
            "**[✔ Click here to review the terms of use and accept the item]({$this->acceptanceUrl()})**",
        ];
    }
}
