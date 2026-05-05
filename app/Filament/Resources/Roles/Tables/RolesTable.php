<?php

namespace App\Filament\Resources\Roles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.roles_model'))
                    ->searchable(),
                TextColumn::make('permissions_count')
                    ->label(__('admin.roles_count'))
                    ->counts('permissions'),
            ])
            ->recordActions([
                EditAction::make()
                    ->authorize(fn (Role $record): bool => Gate::allows('update', $record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords(fn (Role $record): bool => Gate::allows('delete', $record)),
                ]),
            ]);
    }
}
