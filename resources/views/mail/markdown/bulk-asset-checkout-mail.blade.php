<x-mail::message>

<style>
    th, td {
        vertical-align: top;
    }
    hr {
        display: block;
        height: 1px;
        border: 0;
        border-top: 1px solid #ccc;
        margin: 1em 0;
        padding: 0;
    }
</style>

{{ $introduction }}

@if ($requires_acceptance)
One or more items require acceptance.<br>
**[✔ Click here to review the terms of use and accept the items]({{ $acceptance_url }})**
@endif

<hr>

@if ((isset($expected_checkin)) && ($expected_checkin!=''))
**{{ trans('mail.expecting_checkin_date') }}**: {{ Helper::getFormattedDateObject($expected_checkin, 'date', false) }}
@endif

@if ($note)
**{{ trans('mail.additional_notes') }}**: {{ $note }}
@endif

@if ($eula)
<x-mail::panel>
    {{ $eula }}
</x-mail::panel>
@endif

<x-mail::table>
|        |        |
| ------------- | ------------- |
@foreach($assets as $asset)
| **Asset Tag** | <a href="{{ route('hardware.show', $asset->id) }}">{{ $asset->display_name }}</a><br><small>{{trans('mail.serial').': '.$asset->serial}}</small> |
@if (isset($asset->model?->category))
| **{{ trans('general.category') }}** | {{ $asset->model->category->name }} |
@endif
@if (isset($asset->manufacturer))
| **{{ trans('general.manufacturer') }}** | {{ $asset->manufacturer->name }} |
@endif
@if (isset($asset->model))
| **{{ trans('general.asset_model') }}** | {{ $asset->model->name }} |
@endif
@if ((isset($asset->model?->model_number)))
| **{{ trans('general.model_no') }}** | {{ $asset->model->model_number }} |
@endif
@if (isset($asset->assetstatus))
| **{{ trans('general.status') }}** | {{ $asset->assetstatus->name }} |
@endif
| <hr> | <hr> |
@endforeach
</x-mail::table>

**{{ trans('general.administrator') }}**: {{ $admin->display_name }}

{{ trans('mail.best_regards') }}<br>

{{ $snipeSettings->site_name }}
</x-mail::message>
