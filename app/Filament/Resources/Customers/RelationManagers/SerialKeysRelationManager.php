<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SerialKeysRelationManager extends RelationManager
{
    protected static string $relationship = 'serialKeys';

    protected static ?string $recordTitleAttribute = 'key_value';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('key_value')
                    ->label('Serial Key / Activation Code')
                    ->placeholder('Leave empty to auto-generate')
                    ->maxLength(255),
                Forms\Components\TextInput::make('hardware_fingerprint')
                    ->label('PC Hardware Fingerprint')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('max_activations')
                    ->numeric()
                    ->default(1)
                    ->required(),
                Forms\Components\DateTimePicker::make('expires_at')
                    ->default(now()->addYear()),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'revoked' => 'Revoked',
                        'expired' => 'Expired',
                    ])
                    ->default('active')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key_value')
                    ->label('Serial Key')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('hardware_fingerprint')
                    ->label('Hardware Fingerprint')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'suspended' => 'warning',
                        'revoked', 'expired' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('max_activations')
                    ->label('Max Activations'),
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Actions\CreateAction::make(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
