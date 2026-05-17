<?php

namespace App\Filament\Resources\Devices\Pages;

use App\Filament\Resources\Devices\DeviceResource;
use App\Models\Device;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

class EditDevice extends EditRecord
{
    protected static string $resource = DeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('softDelete')
                ->label(__('filament-actions::delete.single.label'))
                ->icon(Heroicon::Trash)
                ->color('danger')
                ->requiresConfirmation()
                ->authorize(fn (Device $record): bool => Gate::allows('delete', $record))
                ->action(function (Device $record): void {
                    $record->update(['isdeleted' => true]);

                    $this->redirect(DeviceResource::getUrl('index'));
                }),
        ];
    }
}
