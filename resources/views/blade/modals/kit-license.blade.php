{{-- $kitId is passed by ModalController::show. See snipeit_modals.js. --}}
<x-modals
    :title="trans('admin/kits/general.append_license')"
    :action="route('api.kits.licenses.store', $kitId)"
    submitToSelect2
    form_class="form-horizontal"
>
    <x-form.row name="license" :label="trans('general.license')" id="modal-license_id" required>
        <x-slot:input>
            <select
                class="js-data-ajax"
                data-endpoint="licenses"
                data-placeholder="{{ trans('general.select_license') }}"
                name="license"
                id="modal-license_id"
                style="width: 100%"
                aria-label="{{ trans('general.license') }}"
                required
            >
                <option value=""></option>
            </select>
        </x-slot:input>
    </x-form.row>

    <x-form.row
        name="quantity"
        :label="trans('general.quantity')"
        id="modal-quantity_id"
        type="number"
        default="1"
        :min="1"
        required
    />
</x-modals>
