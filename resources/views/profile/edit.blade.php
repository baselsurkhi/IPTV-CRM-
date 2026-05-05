<x-app-layout>
    <x-slot name="header">
        <div class="crm-page-title truncate">{{ __('crm.account.heading') }}</div>
        <div class="crm-page-sub truncate">{{ __('crm.account.subheading') }}</div>
    </x-slot>

    <div class="crm-profile-stack">
        <div class="crm-profile-card">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="crm-profile-card">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="crm-profile-card">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
