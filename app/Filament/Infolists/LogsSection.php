<?php

namespace App\Filament\Infolists;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;

class LogsSection
{
    public static function make(): Section
    {
        return Section::make('Logs')
            ->description('Who changed this record, what changed, and when.')
            ->schema([
                RepeatableEntry::make('logs')
                    ->label('')
                    ->placeholder('No logs recorded yet.')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('When')
                            ->dateTime(),
                        TextEntry::make('user.name')
                            ->label('Who')
                            ->placeholder('System'),
                        TextEntry::make('event')
                            ->label('Event')
                            ->badge(),
                        TextEntry::make('description')
                            ->label('What')
                            ->columnSpanFull(),
                        TextEntry::make('changes')
                            ->label('Changed fields')
                            ->formatStateUsing(fn ($state): string => collect($state ?? [])
                                ->keys()
                                ->join(', ') ?: '-')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }
}
