<?php

namespace App\Filament\Resources\Users;

use App\Authorization\PermissionsRegistry;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.group_access');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.users');
    }

    public static function getNavigationSort(): ?int
    {
        return 20;
    }

    public static function getModelLabel(): string
    {
        return __('admin.users_model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.navigation.users');
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->can('viewAny', User::class);
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->can('create', User::class);
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->can('update', $record);
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->can('delete', $record);
    }

    public static function canDeleteAny(): bool
    {
        $u = auth()->user();

        return $u !== null && $u->can(PermissionsRegistry::USERS_DELETE);
    }
}
