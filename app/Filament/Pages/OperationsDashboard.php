<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\OperationsOverview;
use Filament\Pages\Dashboard;

class OperationsDashboard extends Dashboard
{
    public function getWidgets(): array
    {
        return [OperationsOverview::class];
    }

    public function getTitle(): string
    {
        return __('studio.overview');
    }

    public static function getNavigationLabel(): string
    {
        return __('studio.overview');
    }
}
