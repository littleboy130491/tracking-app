<?php

namespace App\Filament\Resources\Containers;

use App\Filament\Resources\Containers\Pages\CreateContainer;
use App\Filament\Resources\Containers\Pages\EditContainer;
use App\Filament\Resources\Containers\Pages\ListContainers;
use App\Filament\Resources\Containers\Pages\ViewContainer;
use App\Filament\Resources\Containers\Schemas\ContainerForm;
use App\Filament\Resources\Containers\Schemas\ContainerInfolist;
use App\Filament\Resources\Containers\Tables\ContainersTable;
use App\Models\Container;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContainerResource extends Resource
{
    protected static ?string $model = Container::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $modelLabel = 'Container';

    protected static ?string $pluralModelLabel = 'Containers';

    protected static ?int $navigationSort = 25;

    public static function form(Schema $schema): Schema
    {
        return ContainerForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContainerInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContainersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['billOfLading.company', 'logs.user']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContainers::route('/'),
            'create' => CreateContainer::route('/create'),
            'view' => ViewContainer::route('/{record}'),
            'edit' => EditContainer::route('/{record}/edit'),
        ];
    }
}
