<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! filled($data['password'] ?? null)) {
            throw ValidationException::withMessages([
                'data.password' => [__('validation.required', ['attribute' => __('filament-panels::auth/pages/edit-profile.form.password.validation_attribute')])],
            ]);
        }

        return $data;
    }
}
