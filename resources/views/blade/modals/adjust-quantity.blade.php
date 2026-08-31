{{-- Action URL is set per-click by snipeit.js from the trigger button's
     data-adjust-url. See snipeit_modals.js's .adjust-quantity delegated
     handler. --}}
<x-modals
    id="adjustQuantityModal"
    form_attrs='id="adjustQuantityForm" enctype="multipart/form-data"'
>
    <x-slot:header>
        <h4 class="modal-title" id="adjustQuantityModalLabel">
            {{ trans('general.adjust_quantity') }}
            <small class="adjust-quantity-item-name text-muted"></small>
        </h4>
    </x-slot:header>

    <p>
        {{ trans('general.available') }}:
        <strong class="adjust-quantity-available"></strong>
    </p>

    <x-form.stacked name="amount">
        <label for="adjustQuantityAmount">{{ trans('general.adjust_quantity_amount') }}</label>
        {{-- min is populated by snipeit.js when the modal opens (–available). The
             browser stepper then refuses to go below and the built-in
             constraint-validation message surfaces if a user types past it.
             Zero is a valid input: it produces an audit-only QuantityAdjust
             log entry with no qty change so users can record a physical
             count against the current DB value. --}}
        <input type="number" class="form-control" id="adjustQuantityAmount" name="amount" step="1" required>
        <x-form.help name="adjustQuantityAmount">{{ trans('general.adjust_quantity_amount_help') }}</x-form.help>
    </x-form.stacked>

    {{-- Acquisition-only fields (order_number, supplier, unit cost,
         currency). Shown for positive qty changes and hidden by snipeit.js
         when the amount is 0 or negative. Those cases are corrections or
         losses rather than purchases, so purchase metadata doesn't apply. --}}
    <div id="adjustQuantityAcquisitionFields">
        <x-form.stacked name="order_number">
            <label for="adjustQuantityOrder">{{ trans('general.order_number') }}</label>
            <input type="text" class="form-control" id="adjustQuantityOrder" name="order_number" maxlength="191">
        </x-form.stacked>

        {{-- Inlined js-data-ajax select instead of x-input.supplier-select
             because that component wraps its markup in x-form.row's
             horizontal-form scaffold (col-md-3 label + col-md-7 input),
             which fights the stacked form-group layout used across this
             modal. snipeit.js auto-initializes any .js-data-ajax select. --}}
        <x-form.stacked name="supplier_id">
            <label for="adjustQuantitySupplier">{{ trans('general.supplier') }}</label>
            <select
                class="js-data-ajax form-control"
                data-endpoint="suppliers"
                data-placeholder="{{ trans('general.select_supplier') }}"
                name="supplier_id"
                id="adjustQuantitySupplier"
                style="width: 100%"
                aria-label="{{ trans('general.supplier') }}"
            >
                <option value=""></option>
            </select>
        </x-form.stacked>
    </div>

    <x-form.stacked name="purchase_date">
        {{-- Label swaps between "Purchase Date" (positive qty)
             and "Date" (0 or negative) via snipeit.js. --}}
        <label
            for="adjustQuantityPurchaseDate"
            id="adjustQuantityPurchaseDateLabel"
            data-label-purchase="{{ trans('general.purchase_date') }}"
            data-label-generic="{{ trans('general.date') }}"
        >{{ trans('general.purchase_date') }}</label>
        {{-- Pre-populated with today's date because most
             adjust events happen the day they are recorded.
             Editable, and snipeit.js resets to today on
             every modal open so a stale date can't bleed
             across sessions. --}}
        <x-input.datepicker
            id="adjustQuantityPurchaseDate"
            name="purchase_date"
            :value="now()->toDateString()"
            :end_date="'0d'"
        />
    </x-form.stacked>

    {{-- Unit cost + currency side by side. Bootstrap 3
         input-group doesn't handle a form-control addon
         cleanly (the addon slot expects a span, not a
         second input), so this uses a plain row / col
         split instead. Currency is editable and left
         blank on open. Pre-filling with the system
         default would assert info we don't have per
         event (Snipe-IT's historical currency handling
         is squishy already). Placeholder hints at the
         system default without stamping it. --}}
    <div class="row" id="adjustQuantityCostRow">
        <x-form.stacked name="unit_cost" class="col-md-8" style="padding-right: 5px;">
            <label for="adjustQuantityUnitCost">{{ trans('general.unit_cost') }}</label>
            <input
                type="number"
                class="form-control"
                id="adjustQuantityUnitCost"
                name="unit_cost"
                step="0.0001"
                min="0"
                inputmode="decimal"
            >
        </x-form.stacked>
        <x-form.stacked name="currency" class="col-md-4" style="padding-left: 5px;">
            <label for="adjustQuantityCurrency">{{ trans('general.currency') }}</label>
            <input
                type="text"
                class="form-control"
                id="adjustQuantityCurrency"
                name="currency"
                maxlength="10"
                placeholder="{{ $snipeSettings->default_currency }}"
            >
        </x-form.stacked>
    </div>
    {{-- Shown by snipeit.js when the modal opens with pre-populated
         cost/currency from the trigger's data-last-* attrs. Hidden as
         soon as the user edits either field, so it disappears the moment
         the pre-fill stops being authoritative. Custom id overrides
         x-form.help's default "{name}-help" id via the attributes merge
         because snipeit.js queries this exact id. --}}
    <x-form.help name="adjustQuantityCost" id="adjustQuantityCostHint" style="display: none; margin-top: -5px;">
        {{ trans('general.adjust_quantity_prefilled_from_last_order') }}
    </x-form.help>

    <x-form.stacked name="note">
        <label for="adjustQuantityNote">{{ trans('general.notes') }}</label>
        <textarea class="form-control" id="adjustQuantityNote" name="note" rows="3" maxlength="65535" required></textarea>
    </x-form.stacked>

    {{-- Inlined instead of x-input.file-upload because that
         component wraps its markup in x-form.row, a Bootstrap 3
         horizontal-form scaffold (col-md-3 label + col-md-9
         input). The three fields above use vertical form-group
         styling, so the horizontal scaffold collided with the
         modal-footer top border. --}}
    <x-form.stacked name="file">
        <label for="adjustQuantityFile">{{ trans('general.file_upload') }}</label>
        <div>
            <label class="btn btn-sm btn-theme" for="adjustQuantityFile">
                {{ trans('button.select_file') }}
                <input
                    type="file"
                    name="file"
                    class="js-uploadFile"
                    id="adjustQuantityFile"
                    data-maxsize="{{ \App\Helpers\Helper::file_upload_max_size() }}"
                    accept="{{ config('filesystems.allowed_upload_mimetypes') }}"
                    style="display:none"
                    aria-label="file"
                    aria-hidden="true"
                >
            </label>
            <span id="adjustQuantityFile-info"></span>
            <x-form.help name="adjustQuantityFile">
                {{ trans('general.upload_filetypes_help', ['allowed_filetypes' => config('filesystems.allowed_upload_extensions'), 'size' => \App\Helpers\Helper::file_upload_max_size_readable()]) }}
            </x-form.help>
        </div>
    </x-form.stacked>
</x-modals>
