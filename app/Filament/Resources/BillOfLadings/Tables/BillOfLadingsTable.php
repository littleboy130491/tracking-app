<?php

namespace App\Filament\Resources\BillOfLadings\Tables;

use App\Models\BillOfLading;
use App\Models\User;
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
use Illuminate\Support\Facades\DB;

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
                TextColumn::make('containers.container_number')
                    ->label('Container Numbers')
                    ->searchable()
                    ->listWithLineBreaks()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('customer.company_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->placeholder(fn ($record): string => $record->customer?->name ?? '-'),
                TextColumn::make('shipment_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => config("bl_workflows.shipment_types.{$state}", $state ?? '-'))
                    ->sortable(),
                TextColumn::make('shipping_method')
                    ->label('Method')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => config("bl_workflows.shipping_methods.{$state}", strtoupper($state ?? '-')))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('carrier_name')
                    ->label('Carrier')
                    ->toggleable()
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('port_of_loading')
                    ->label('POL')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),
                TextColumn::make('port_of_discharge')
                    ->label('POD')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('phase')
                    ->label('Milestone')
                    ->badge()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('customs_lane')
                    ->label('Lane')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): ?string => $state ? config("bl_workflows.customs_lanes.{$state}") : null)
                    ->placeholder('-')
                    ->toggleable(),
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
                SelectFilter::make('current_milestone_key')
                    ->label('Milestone')
                    ->options(fn (): array => BillOfLading::milestoneOptions()),
                SelectFilter::make('shipment_type')
                    ->label('Shipment Type')
                    ->options(config('bl_workflows.shipment_types')),
                SelectFilter::make('shipping_method')
                    ->label('Shipping Method')
                    ->options(config('bl_workflows.shipping_methods')),
                SelectFilter::make('customs_lane')
                    ->label('Customs Lane')
                    ->options(config('bl_workflows.customs_lanes')),
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
                    ->options(fn (): array => self::yearOptions())
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
            ->toolbarActions([]);
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

    /**
     * @return array<string, string>
     */
    private static function yearOptions(): array
    {
        $expression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y', input_date)"
            : 'YEAR(input_date)';

        return BillOfLading::query()
            ->whereNotNull('input_date')
            ->selectRaw("{$expression} as input_year")
            ->distinct()
            ->orderByDesc('input_year')
            ->pluck('input_year')
            ->mapWithKeys(fn ($year): array => [(string) $year => (string) $year])
            ->all();
    }
}
