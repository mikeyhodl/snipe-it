<x-mail::message>
{{ $introduction }}

**{{ trans('general.administrator') }}**: {{ $admin->display_name }}

@if ($requires_acceptance == 1)
One or more items require acceptance.<br>
**[✔ Click here to review the terms of use and accept the items]({{ $acceptance_url }})**
@endif

{{ trans('mail.best_regards') }}<br>

{{ $snipeSettings->site_name }}
</x-mail::message>
