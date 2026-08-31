{{-- $kitId is passed by ModalController::show. See snipeit_modals.js. --}}
<x-modals
    :title="trans('admin/kits/general.append_accessory')"
    :action="route('api.kits.accessories.store', $kitId)"
    submitToSelect2
    form_class="form-horizontal"
>
    <x-form.row name="accessory" :label="trans('general.accessory')" id="modal-accessory_id" required>
        <x-slot:input>
            <select
                class="js-data-ajax"
                data-endpoint="accessories"
                data-placeholder="{{ trans('general.select_accessory') }}"
                name="accessory"
                id="modal-accessory_id"
                style="width: 100%"
                aria-label="{{ trans('general.accessory') }}"
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
