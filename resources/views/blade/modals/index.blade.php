@props([
    'id' => null,
    'title' => null,
    'action' => null,
    'ajax' => false,
    'submit_label' => null,
    'submit_class' => 'btn-primary',
    'labelledby' => null,
    'form_attrs' => '',
    'form_class' => null,
])

@php
    $submitLabel = $submit_label ?? trans('general.save');
    $labelledById = $labelledby ?? ($id ? $id.'Label' : null);
    $onsubmit = $ajax ? ' onsubmit="return false"' : '';
    $saveButtonAttrs = $ajax ? ' type="button" id="modal-save"' : ' type="submit"';
@endphp

@if ($id)
<div class="modal fade" id="{{ $id }}" tabindex="-1" role="dialog" @if ($labelledById) aria-labelledby="{{ $labelledById }}" @endif aria-hidden="true">
@endif
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('button.close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
                @isset($header)
                    {{ $header }}
                @else
                    <h4 class="modal-title" @if ($labelledById) id="{{ $labelledById }}" @endif>{{ $title }}</h4>
                @endisset
            </div>

            <form action="{{ $action }}" method="POST" @if ($form_class) class="{{ $form_class }}" @endif{!! $onsubmit !!} {!! $form_attrs !!}>
                @csrf

                @if ($ajax)
                    <x-alert type="danger" id="modal_error_msg" style="display:none"></x-alert>
                @endif

                <div class="modal-body">
                    {{ $slot }}
                </div>

                <div class="modal-footer">
                    @isset($footer)
                        {{ $footer }}
                    @else
                        <button type="button" class="btn btn-default pull-left" data-dismiss="modal">{{ trans('button.cancel') }}</button>
                        <button class="btn {{ $submit_class }} pull-right"{!! $saveButtonAttrs !!}>{{ $submitLabel }}</button>
                    @endisset
                </div>
            </form>
        </div>
    </div>
@if ($id)
</div>
@endif
