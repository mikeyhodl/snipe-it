{{-- $kitId is passed by ModalController::show. See snipeit_modals.js. --}}
<x-modals
    :title="trans('admin/kits/general.append_accessory')"
    :action="route('api.kits.accessories.store', $kitId)"
    submitToSelect2
    form_class="form-horizontal"
>
    <x-input.accessory-select
        name="accessory"
        id="modal_accessory_select"
        :label="trans('general.accessory')"
        required
        hideNewButton
    />

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
