{{-- $kitId is passed by ModalController::show. See snipeit_modals.js. --}}
<x-modals
    :title="trans('admin/kits/general.append_consumable')"
    :action="route('api.kits.consumables.store', $kitId)"
    submitToSelect2
    form_class="form-horizontal"
>
    <x-form.row name="consumable" :label="trans('general.consumable')" id="modal-consumable_id" required>
        <x-slot:input>
            <select
                class="js-data-ajax"
                data-endpoint="consumables"
                data-placeholder="{{ trans('general.select_consumable') }}"
                name="consumable"
                id="modal-consumable_id"
                style="width: 100%"
                aria-label="{{ trans('general.consumable') }}"
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
