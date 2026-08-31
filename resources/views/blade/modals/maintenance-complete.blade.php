{{-- Confirmation modal for marking a maintenance complete. Rendered on any
     page that lists maintenances via `x-table.maintenances`. The bootstrap-
     table actions formatter that emits the green checkmark button lives in
     partials/bootstrap-table.blade.php; the click handler that populates the
     form action and shows this modal lives in resources/assets/js/snipeit.js
     as a delegated `.complete-maintenance` handler. --}}
<x-modals
    id="completeMaintenanceModal"
    :title="trans('admin/maintenances/form.mark_complete')"
    :submit_label="trans('admin/maintenances/form.mark_complete')"
    submit_class="btn-success"
    form_attrs='id="completeMaintenanceForm"'
>
    <p>{{ trans('admin/maintenances/message.complete.confirm') }}</p>
    <x-form.stacked name="note">
        <label for="completionNote">{{ trans('admin/maintenances/form.completion_notes') }}</label>
        <textarea class="form-control" id="completionNote" name="note" rows="3"></textarea>
    </x-form.stacked>
</x-modals>
