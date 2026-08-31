{{-- Action URL is set per-click by snipeit.js from the trigger button's
     data-request-url. Same shape as the adjust-quantity modal so the
     moving parts stay familiar. --}}
<x-modals
    id="requestItemModal"
    :submit_label="trans('button.request')"
    form_attrs='id="requestItemForm" accept-charset="utf-8"'
>
    <x-slot:header>
        <h4 class="modal-title" id="requestItemModalLabel">
            {{ trans('general.request_item') }}
            <small class="request-item-name text-muted"></small>
        </h4>
    </x-slot:header>

    {{-- Snapshot of the tab the user was on when they
         opened the modal. Snipeit.js reads the enclosing
         .tab-pane on click and writes its id here so the
         controller can restore the same tab on the
         post-submit redirect. --}}
    <input type="hidden" name="active_tab" id="requestItemActiveTab" value="">

    {{-- Qty row is hidden when the trigger sets
         data-item-type="asset". Assets are 1:1
         (you request THE asset, not N of it). The
         hidden input keeps request-quantity=1
         posted so the server-side path stays
         uniform across every requestable type. --}}
    <x-form.stacked name="request-quantity" id="requestItemQuantityRow">
        <label for="requestItemQuantity">{{ trans('general.qty') }}</label>
        <input
            type="number"
            class="form-control"
            id="requestItemQuantity"
            name="request-quantity"
            min="1"
            step="1"
            value="1"
            required
        >
    </x-form.stacked>

    {{-- Start / end dates are optional. Requesters who just
         want "whenever this becomes available" leave both
         blank; requesters reserving for a specific window
         (offsite event, project sprint) fill them in.
         end_date validates as after_or_equal:start_date in
         the controller. --}}
    <div class="row">
        <x-form.stacked name="start_date" class="col-md-6">
            <label for="requestItemStartDate">{{ trans('general.start_date') }}</label>
            <x-input.datepicker
                id="requestItemStartDate"
                name="start_date"
            />
        </x-form.stacked>
        <x-form.stacked name="end_date" class="col-md-6">
            <label for="requestItemEndDate">{{ trans('general.end_date') }}</label>
            <x-input.datepicker
                id="requestItemEndDate"
                name="end_date"
            />
        </x-form.stacked>
    </div>

    {{-- Optional free-text notes so the requester can
         attach context (why they need it, budget code,
         project, etc). Persisted on
         checkout_requests.notes and surfaced on the
         admin queue + in the "new request"
         notification. --}}
    <x-form.stacked name="notes">
        <label for="requestItemNotes">{{ trans('general.notes') }}</label>
        <textarea
            id="requestItemNotes"
            name="notes"
            class="form-control"
            rows="3"
        ></textarea>
    </x-form.stacked>
</x-modals>
