<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Activity Logs';

    protected static ?string $modelLabel = 'Activity Log';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 99;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && $user->isAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Activity Details')
                    ->schema([
                        Forms\Components\TextInput::make('action')
                            ->label('Action')
                            ->disabled(),
                            
                        Forms\Components\TextInput::make('action_type')
                            ->label('Action Type')
                            ->disabled(),
                            
                        Forms\Components\TextInput::make('resource_type')
                            ->label('Resource Type')
                            ->disabled(),
                            
                        Forms\Components\TextInput::make('resource_id')
                            ->label('Resource ID')
                            ->disabled(),
                            
                        Forms\Components\TextInput::make('resource_name')
                            ->label('Resource Name')
                            ->disabled(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'name')
                            ->disabled(),
                            
                        Forms\Components\TextInput::make('ip_address')
                            ->label('IP Address')
                            ->disabled(),
                            
                        Forms\Components\Textarea::make('user_agent')
                            ->label('User Agent')
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Additional Details')
                    ->schema([
                        Forms\Components\KeyValue::make('details')
                            ->label('Details')
                            ->disabled(),
                            
                        Forms\Components\TextInput::make('created_at')
                            ->label('Created At')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('action')
                    ->label('Action')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('action_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'create' => 'success',
                        'update' => 'warning',
                        'delete' => 'danger',
                        'restore' => 'info',
                        'force_delete' => 'danger',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('resource_type')
                    ->label('Resource')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('resource_name')
                    ->label('Resource Name')
                    ->searchable()
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('action_type')
                    ->label('Action Type')
                    ->options([
                        'create' => 'Create',
                        'update' => 'Update',
                        'delete' => 'Delete',
                        'restore' => 'Restore',
                        'force_delete' => 'Force Delete',
                    ]),
                    
                Tables\Filters\SelectFilter::make('resource_type')
                    ->label('Resource Type')
                    ->options(function () {
                        return ActivityLog::distinct('resource_type')
                            ->pluck('resource_type', 'resource_type')
                            ->toArray();
                    }),
                    
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('User')
                    ->options(function () {
                        return User::where('role', 'staff')
                            ->pluck('name', 'id')
                            ->toArray();
                    }),
                    
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }
}
