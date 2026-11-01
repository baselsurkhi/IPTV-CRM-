<?php

namespace App\Filament\Resources\Devices\Pages;

use App\Filament\Resources\Devices\Actions\ManageSubscription;
use App\Filament\Resources\Devices\DeviceResource;
use App\Models\Device;
use App\Services\SaveDeviceSubscription;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class EditDevice extends EditRecord
{
    protected static string $resource = DeviceResource::class;

    public function getTitle(): string
    {
        return __('workflow.manage');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(SaveDeviceSubscription::class)->save($record, $data);
    }

    protected function getRedirectUrl(): string
    {
        return DeviceResource::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            ManageSubscription::make(),
            Action::make('softDelete')
                ->label(__('filament-actions::delete.single.label'))->visible(fn (Device $record): bool => ! $record->isdeleted)
                ->icon(Heroicon::Trash)
                ->color('danger')
                ->requiresConfirmation()
                ->authorize(fn (Device $record): bool => Gate::allows('delete', $record))
                ->action(function (Device $record): void {
                    $record->moveToDeleted();

                    $this->redirect(DeviceResource::getUrl('index'));
                }),
        ];
    }
}
