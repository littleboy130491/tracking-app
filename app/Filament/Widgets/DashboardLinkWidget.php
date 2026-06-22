<?php

namespace App\Filament\Widgets;

use BackedEnum;
use Filament\Widgets\Widget;

class DashboardLinkWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    /**
     * @var view-string
     */
    protected string $view = 'filament.widgets.dashboard-link-widget';

    public string $label;

    public string $description;

    public string $url;

    public string | BackedEnum $icon;
}
