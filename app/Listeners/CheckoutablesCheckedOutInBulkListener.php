<?php

namespace App\Listeners;

use App\Events\CheckoutablesCheckedOutInBulk;
use App\Mail\BulkAssetCheckoutMail;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class CheckoutablesCheckedOutInBulkListener
{

    public function subscribe($events)
    {
        $events->listen(
            CheckoutablesCheckedOutInBulk::class,
            CheckoutablesCheckedOutInBulkListener::class
        );
    }

    public function handle(CheckoutablesCheckedOutInBulk $event): void
    {
        $shouldSendEmailToUser = $this->shouldSendCheckoutEmailToUser($event->assets);
        $shouldSendEmailToAlertAddress = $this->shouldSendEmailToAlertAddress($event->assets);

        if ($shouldSendEmailToUser && $event->target->email) {
            Mail::to($event->target)->send(new BulkAssetCheckoutMail(
                $event->assets,
                $event->target,
                $event->admin,
                $event->checkout_at,
                $event->expected_checkin,
                $event->note,
            ));
        }

        if ($shouldSendEmailToAlertAddress && Setting::getSettings()->admin_cc_email) {
            Mail::to(Setting::getSettings()->admin_cc_email)->send(new BulkAssetCheckoutMail(
                $event->assets,
                $event->target,
                $event->admin,
                $event->checkout_at,
                $event->expected_checkin,
                $event->note,
            ));
        }
    }

    private function shouldSendCheckoutEmailToUser(Collection $assets): bool
    {
        // @todo: how to handle assets having eula?

        return $this->requiresAcceptance($assets);
    }

    private function shouldSendEmailToAlertAddress(Collection $assets): bool
    {
        $setting = Setting::getSettings();

        if (!$setting) {
            return false;
        }

        if ($setting->admin_cc_always) {
            return true;
        }

        if (!$this->requiresAcceptance($assets)) {
            return false;
        }

        return (bool) $setting->admin_cc_email;
    }

    private function requiresAcceptance(Collection $assets): bool
    {
        return (bool) $assets->reduce(
            fn($count, $asset) => $count + $asset->requireAcceptance()
        );
    }
}
