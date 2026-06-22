<x-filament-widgets::widget class="fi-dashboard-link-widget">
    <a href="{{ $url }}" class="group block">
        <x-filament::section
            :heading="$label"
            :description="$description"
            :icon="$icon"
        />
    </a>
</x-filament-widgets::widget>
