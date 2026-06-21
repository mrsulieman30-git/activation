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

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Infolists\Components\Section::make('Request Details')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('clinic_name'),
                        \Filament\Infolists\Components\TextEntry::make('contact_name'),
                        \Filament\Infolists\Components\TextEntry::make('email'),
                        \Filament\Infolists\Components\TextEntry::make('phone'),
                        \Filament\Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'gray',
                            }),
                    ])->columns(2),
                \Filament\Infolists\Components\Section::make('Device & Serial Key')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('device_fingerprint'),
                        \Filament\Infolists\Components\TextEntry::make('serialKey.key_value')
                            ->label('Assigned Serial Key')
                            ->copyable()
                            ->copyMessage('Serial key copied')
                            ->copyMessageDuration(1500),
                    ])->columns(2),
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
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\Action::make('approve')
                    ->label('Approve & Generate Key')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->hidden(fn (RegistrationRequest $record) => $record->status !== 'pending')
                    ->action(function (RegistrationRequest $record) {
                        // 1. Create Customer Profile
                        $customer = Customer::firstOrCreate(
                            ['contact_email' => $record->email],
                            [
                                'name' => $record->clinic_name,
                                'contact_person' => $record->contact_name,
                                'contact_phone' => $record->phone,
                                'code' => 'HOSP-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(6)),
                                'max_devices' => 10,
                                'license_type' => 'hospital',
                            ]
                        );

                        // 2. Generate a Serial Key for this customer
                        $serialKeyStr = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(4)) . '-' . 
                                        \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(4)) . '-' . 
                                        \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(4)) . '-' . 
                                        \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(4));

                        $serialKey = SerialKey::create([
                            'customer_id' => $customer->id,
                            'key_value' => $serialKeyStr,
                            'max_activations' => 1,
                            'expires_at' => now()->addYear(),
                            'is_active' => true,
                        ]);

                        // 3. Update the Registration Request
                        $record->update([
                            'status' => 'approved',
                            'serial_key_id' => $serialKey->id,
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                        
                        // Let Filament know it succeeded so it can show a toast notification
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Registration Approved!')
                            ->body("Serial Key {$serialKeyStr} generated for {$record->clinic_name}.")
                            ->send();

                        // Redirect to the view page of this request
                        $record->refresh();
                        return redirect(RegistrationRequestResource::getUrl('view', ['record' => $record]));
                    }),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
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
            'view' => Pages\ViewRegistrationRequest::route('/{record}'),
            'edit' => Pages\EditRegistrationRequest::route('/{record}/edit'),
        ];
    }
}
