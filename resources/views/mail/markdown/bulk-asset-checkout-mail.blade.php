<x-mail::message>
# Introduction

{{ $introduction }}

<x-mail::button :url="''">
Button Text
</x-mail::button>

{{ trans('mail.best_regards') }}<br>

{{ $snipeSettings->site_name }}
</x-mail::message>
