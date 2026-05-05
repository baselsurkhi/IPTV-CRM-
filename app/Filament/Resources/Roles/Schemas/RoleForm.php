<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.settings_general'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.roles_model'))
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                table: Role::class,
                                column: 'name',
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule): mixed => $rule->where('guard_name', 'web'),
                            ),
                        CheckboxList::make('permissions')
                            ->relationship('permissions', 'name')
                            ->columns(3)
                            ->bulkToggleable()
                            ->searchable(),
                    ]),
            ]);
    }
}
