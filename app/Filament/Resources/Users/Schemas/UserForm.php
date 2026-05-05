<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use App\Support\Locales;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.settings_general'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('filament-panels::auth/pages/edit-profile.form.name.label'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('filament-panels::auth/pages/edit-profile.form.email.label'))
                            ->email()
                            ->required()
                            ->unique(table: User::class, column: 'email', ignoreRecord: true)
                            ->maxLength(255),
                        Select::make('locale')
                            ->label(__('admin.profile_locale'))
                            ->options(Locales::filamentLabels())
                            ->native(false)
                            ->required(),
                        CheckboxList::make('roles')
                            ->relationship('roles', 'name')
                            ->columns(3)
                            ->bulkToggleable()
                            ->searchable(),
                        TextInput::make('password')
                            ->label(__('filament-panels::auth/pages/edit-profile.form.password.label'))
                            ->password()
                            ->revealable()
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->rule(Password::min(8))
                            ->autocomplete('new-password'),
                    ]),
            ]);
    }
}
