<?php

namespace App\Filament\Auth;

use App\Support\Locales;
use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

/**
 * صفحة الملف الشخصي مع اختيار لغة واجهة Filament (ar / en / he).
 */
class EditProfilePage extends EditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                Select::make('locale')
                    ->label(__('admin.profile_locale'))
                    ->options(Locales::filamentLabels())
                    ->native(false)
                    ->required(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
            ]);
    }
}
