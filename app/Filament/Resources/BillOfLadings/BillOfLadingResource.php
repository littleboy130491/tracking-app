<?php

namespace App\Filament\Resources\BillOfLadings;

use App\Filament\Resources\BillOfLadings\Pages\CreateBillOfLading;
use App\Filament\Resources\BillOfLadings\Pages\EditBillOfLading;
use App\Filament\Resources\BillOfLadings\Pages\ListBillOfLadings;
use App\Filament\Resources\BillOfLadings\Pages\ViewBillOfLading;
use App\Filament\Resources\BillOfLadings\Schemas\BillOfLadingForm;
use App\Filament\Resources\BillOfLadings\Schemas\BillOfLadingInfolist;
use App\Filament\Resources\BillOfLadings\Tables\BillOfLadingsTable;
use App\Models\BillOfLading;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BillOfLadingResource extends Resource
{
    protected static ?string $model = BillOfLading::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $modelLabel = 'BL Record';

    protected static ?string $pluralModelLabel = 'BL Records';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return BillOfLadingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BillOfLadingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BillOfLadingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['company', 'containers', 'logs.user']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBillOfLadings::route('/'),
            'create' => CreateBillOfLading::route('/create'),
            'view' => ViewBillOfLading::route('/{record}'),
            'edit' => EditBillOfLading::route('/{record}/edit'),
        ];
    }
}
