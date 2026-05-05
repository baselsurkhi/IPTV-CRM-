<?php

namespace App\Filament\Pages;

use App\Authorization\PermissionsRegistry;
use App\Models\SiteSetting;
use App\Support\Locales;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageSiteSettings extends Page
{
    /** @var array<string, mixed> */
    public ?array $data = [];

    protected static ?int $navigationSort = 90;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.group_system');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.settings');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user && $user->can(PermissionsRegistry::SETTINGS_MANAGE));
    }

    public function mount(): void
    {
        $this->form->fill([
            'iptv_player_api_base_url' => SiteSetting::getValue('iptv.player_api_base_url') ?? config('iptv.player_api_base_url'),
            'api_default_locale' => SiteSetting::getValue('api.default_locale') ?? config('iptv.api_fallback_locale', 'ar'),
        ]);
    }

    public static function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.settings_general'))
                    ->schema([
                        TextInput::make('iptv_player_api_base_url')
                            ->label(__('admin.default_player_api_url'))
                            ->url()
                            ->maxLength(500),
                        Select::make('api_default_locale')
                            ->label(__('admin.api_fallback_locale'))
                            ->options(Locales::labels())
                            ->native(false)
                            ->helperText(__('admin.api_fallback_locale_hint')),
                    ]),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('saveSettings')
                ->label(__('admin.save_settings'))
                ->submit('saveSettings'),
        ];
    }

    public function saveSettings(): void
    {
        $data = $this->form->getState();
        SiteSetting::setValue('iptv.player_api_base_url', $data['iptv_player_api_base_url'] ?: null);
        SiteSetting::setValue('api.default_locale', $data['api_default_locale'] ?: 'ar');

        Notification::make()
            ->success()
            ->title(__('admin.settings_saved'))
            ->send();
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('saveSettings')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->key('form-actions'),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.settings_page_title');
    }
}
