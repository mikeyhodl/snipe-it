{{-- Vertical form-group wrapper for stacked-form contexts (modals, filter
     bars, anywhere the parent form isn't .form-horizontal). Use x-form.row
     when the parent form is .form-horizontal and you want the full col-md-3
     label + col-md-7 input grid layout. --}}
@props([
    'name' => null,
])

<div {{ $attributes->class([
    'form-group',
    'has-error' => $name && $errors->has($name),
]) }}>
    {{ $slot }}
</div>
