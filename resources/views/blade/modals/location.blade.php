{{-- See snipeit_modals.js for what powers this --}}
<x-modals
    :title="trans('admin/locations/table.create')"
    :action="route('api.locations.store')"
    submitToSelect2
    form_class="form-horizontal"
>
    <x-form.row name="name" :label="trans('general.name')" id="modal-name" required />

    {{-- Scoped-locations FMCS pins the new location to the creator's
         first company. When enabled we submit company_id as a hidden
         field and skip the picker. Otherwise the user picks the
         company via select2. --}}
    @if (($snipeSettings->scope_locations_fmcs == '1') && (auth()->user()->companies->isNotEmpty()))
        <input type="hidden" name="company_id" id="modal-company_id" value="{{ auth()->user()->companies->first()->id }}">
    @else
        <x-input.company-select
            name="company_id"
            :label="trans('general.company')"
            hideNewButton
        />
    @endif

    <x-form.row name="city" :label="trans('general.city')" id="modal-city" />

    <x-form.row name="country" :label="trans('general.country')">
        <x-slot:input>
            <x-input.country-select
                name="country"
                :selected="old('country')"
                id="modal-country"
            />
        </x-slot:input>
    </x-form.row>
</x-modals>
