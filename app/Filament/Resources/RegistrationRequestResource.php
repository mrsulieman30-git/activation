<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegistrationRequestResource\Pages;
use App\Models\Customer;
use App\Models\RegistrationRequest;
use App\Models\SerialKey;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class RegistrationRequestResource extends Resource
{
    protected static ?string $model = RegistrationRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Registrations';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('clinic_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('contact_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('device_fingerprint')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('clinic_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contact_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('serialKey.key_value')
                    ->label('Serial Key')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Serial key copied')
                    ->copyMessageDuration(1500),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve & Generate Key')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (RegistrationRequest $record) => $record->status === 'pending')
                    ->action(function (RegistrationRequest $record) {
                        // 1. Create Customer
                        $customerCode = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $record->clinic_name), 0, 5));
                        $customer = Customer::firstOrCreate(
                            ['code' => $customerCode],
                            [
                                'name' => $record->clinic_name,
                                'hms_server_url' => 'https://hms.seeha.tech',
                                'hms_api_url' => 'https://hms.seeha.tech/api',
                                'max_devices' => 5,
                                'license_type' => 'clinic',
                                'status' => 'active'
                            ]
                        );

                        // 2. Generate Serial Key
                        $keyValue = 'ST-' . $customerCode . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
                        $serialKey = SerialKey::create([
                            'customer_id' => $customer->id,
                            'key_value' => $keyValue,
                            'status' => 'active',
                            'max_activations' => $customer->max_devices,
                        ]);

                        // 3. Mark request as approved
                        $record->update([
                            'status' => 'approved',
                            'serial_key_id' => $serialKey->id,
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegistrationRequests::route('/'),
            'create' => Pages\CreateRegistrationRequest::route('/create'),
            'edit' => Pages\EditRegistrationRequest::route('/{record}/edit'),
        ];
    }
}
