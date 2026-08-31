{{-- See snipeit_modals.js for what powers this. The password-generator
     wiring and first-input focus live in the modal-load callback there
     so the modal partial stays script-free. --}}
<x-modals
    :title="trans('admin/users/table.createuser')"
    :action="route('api.users.store')"
    submitToSelect2
    form_class="form-horizontal"
>
    @if ($user->companies->isNotEmpty())
        <input type="hidden" name="company_id" value="{{ $user->companies->first()->id }}">
    @endif

    <x-input.company-select
        name="company_id"
        :label="trans('general.company')"
        hideNewButton
    />

    <x-input.location-select
        name="location_id"
        :label="trans('general.location')"
        :selected="null"
        hideNewButton
    />

    <x-form.row name="first_name" :label="trans('general.first_name')" id="modal-first_name" required />
    <x-form.row name="last_name" :label="trans('general.last_name')" id="modal-last_name" required />
    <x-form.row name="email" :label="trans('admin/users/table.email')" id="modal-email" type="email" />
    <x-form.row name="username" :label="trans('admin/users/table.username')" id="modal-username" required />

    {{-- Activated checkbox lives above the password fields because
         snipeit.js toggles password-field visibility off this checkbox.
         Defaults to unchecked because the modal is typically used to
         create a user on-the-fly for asset assignment, where login is
         not usually needed. --}}
    <div class="dynamic-form-row">
        <div class="col-md-offset-3 col-md-9">
            <label class="form-control">
                <input type="checkbox" value="1" name="activated" id="modal-activated" aria-label="activated">
                {{ trans('general.login_enabled') }}
            </label>
            <x-form.help name="modal-activated" icon="tip">
                {{ trans('admin/users/general.activated_password_required_help') }}
            </x-form.help>
        </div>
    </div>

    {{-- Password + confirmation start hidden, revealed by snipeit.js
         when the activated checkbox is checked. --}}
    <x-form.row name="password" :label="trans('admin/users/table.password')" style="display: none;">
        <x-slot:input>
            <div class="input-group">
                <input type="password" name="password" id="modal-password" class="form-control" required>
                <span class="input-group-addon">
                    <i data-toggle="#modal-password" class="fa fa-fw fa-eye toggle-password" aria-hidden="true"></i>
                    <span class="sr-only">{{ trans('general.toggle_password_visibility') }}</span>
                </span>
            </div>
        </x-slot:input>
        <x-slot:after_input>
            <a href="#" class="btn btn-theme btn-sm" id="modal-genPassword" data-tooltip="true" title="{{ trans('admin/users/general.generate_password') }}">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
            </a>
        </x-slot:after_input>
    </x-form.row>

    <x-form.row name="password_confirmation" :label="trans('admin/users/table.password_confirm')" style="display: none;">
        <x-slot:input>
            <div class="input-group">
                <input type="password" name="password_confirmation" id="modal-password_confirmation" class="form-control" required>
                <span class="input-group-addon">
                    <i data-toggle="#modal-password_confirmation" class="fa fa-fw fa-eye toggle-password" aria-hidden="true"></i>
                    <span class="sr-only">{{ trans('general.toggle_password_visibility') }}</span>
                </span>
            </div>
        </x-slot:input>
    </x-form.row>

    <x-form.row name="display_name" :label="trans('admin/users/table.display_name')" id="modal-display_name" />
</x-modals>
