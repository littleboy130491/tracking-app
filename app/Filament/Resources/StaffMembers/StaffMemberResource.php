<?php

namespace App\Filament\Resources\StaffMembers;

use App\Filament\Resources\StaffMembers\Pages\CreateStaffMember;
use App\Filament\Resources\StaffMembers\Pages\EditStaffMember;
use App\Filament\Resources\StaffMembers\Pages\ListStaffMembers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StaffMemberResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $modelLabel = 'Staff Member';

    protected static ?string $pluralModelLabel = 'Staff';

    protected static ?int $navigationSort = 15;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Staff access')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->minLength(12)
                            ->maxLength(255),
                        Select::make('workflow_roles')
                            ->label('Workflow responsibilities')
                            ->options(User::WORKFLOW_ROLE_LABELS)
                            ->multiple()
                            ->searchable()
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Account active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('workflow_roles')
                    ->label('Responsibilities')
                    ->state(fn (User $record): string => $record->roles
                        ->whereIn('name', User::WORKFLOW_ROLES)
                        ->map(fn ($role): string => User::WORKFLOW_ROLE_LABELS[$role->name] ?? $role->name)
                        ->join(', '))
                    ->placeholder('No workflow role')
                    ->wrap(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('last_login_at')
                    ->label('Last login')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->role(User::ROLE_PANEL_USER);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaffMembers::route('/'),
            'create' => CreateStaffMember::route('/create'),
            'edit' => EditStaffMember::route('/{record}/edit'),
        ];
    }
}
