<?php

namespace App\Filament\Resources\BillOfLadings\Tables;

use App\Models\BillOfLading;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BillOfLadingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bl_number')
                    ->label('BL Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.company_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->placeholder(fn ($record): string => $record->customer?->name ?? 'Not provided'),
                TextColumn::make('origin')
                    ->label('Origin')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Not provided'),
                TextColumn::make('destination')
                    ->label('Destination')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Not provided'),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('phase')
                    ->badge()
                    ->sortable(),
                TextColumn::make('input_date')
                    ->label('Input Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Last Update')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(array_combine(BillOfLading::STATUSES, BillOfLading::STATUSES)),
                SelectFilter::make('phase')
                    ->options(array_combine(BillOfLading::PHASES, BillOfLading::PHASES)),
                Filter::make('input_date')
                    ->label('Input Date')
                    ->schema([
                        DatePicker::make('date')
                            ->label('Input Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['date'] ?? null),
                            fn (Builder $query): Builder => $query->whereDate('input_date', $data['date']),
                        );
                    })
                    ->indicateUsing(function (array $data): array {
                        if (blank($data['date'] ?? null)) {
                            return [];
                        }

                        return [
                            Indicator::make('Input date: '.$data['date']),
                        ];
                    }),
                SelectFilter::make('month')
                    ->label('Month')
                    ->options(self::monthOptions())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->whereMonth('input_date', (int) $data['value']),
                    )),
                SelectFilter::make('year')
                    ->label('Year')
                    ->options(fn (): array => BillOfLading::query()
                        ->whereNotNull('input_date')
                        ->get(['input_date'])
                        ->map(fn (BillOfLading $billOfLading): int => $billOfLading->input_date->year)
                        ->unique()
                        ->sortDesc()
                        ->mapWithKeys(fn (int $year): array => [(string) $year => (string) $year])
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->whereYear('input_date', (int) $data['value']),
                    )),
                SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->relationship(
                        name: 'customer',
                        titleAttribute: 'company_name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query
                            ->role(User::ROLE_CUSTOMER)
                            ->orderBy('company_name'),
                    )
                    ->getOptionLabelFromRecordUsing(fn (User $record): string => $record->company_name ?? $record->name)
                    ->searchable()
                    ->preload(),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->defaultSort('updated_at', 'desc')
            ->emptyStateIcon(Heroicon::OutlinedDocumentText)
            ->emptyStateHeading('No BL records yet')
            ->emptyStateDescription('Create a BL record and assign it to a customer.')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function monthOptions(): array
    {
        return collect(range(1, 12))
            ->mapWithKeys(fn (int $month): array => [
                (string) $month => now()->startOfYear()->month($month)->format('F'),
            ])
            ->all();
    }
}
