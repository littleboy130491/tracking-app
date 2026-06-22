<?php

namespace App\Filament\Pages;

use App\Filament\Resources\BillOfLadings\BillOfLadingResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\DashboardLinkWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    public function getHeading(): string | Htmlable | null
    {
        return 'Hello '.Auth::user()->name;
    }

    public function getColumns(): int | array
    {
        return [
            'default' => 1,
            'md' => 2,
        ];
    }

    /**
     * @return array<class-string<\Filament\Widgets\Widget> | \Filament\Widgets\WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            DashboardLinkWidget::make([
                'label' => 'Manage BL Records',
                'description' => 'Create BL records and post progress updates for customers.',
                'url' => BillOfLadingResource::getUrl(),
                'icon' => Heroicon::OutlinedDocumentText,
            ]),
            DashboardLinkWidget::make([
                'label' => 'Manage Customers',
                'description' => 'Create and update customer accounts.',
                'url' => UserResource::getUrl(),
                'icon' => Heroicon::OutlinedUsers,
            ]),
        ];
    }
}
