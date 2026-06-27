<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegistrationRequestResource\Pages;
use App\Models\Customer;
use App\Models\RegistrationRequest;
use App\Models\SerialKey;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class RegistrationRequestResource extends Resource
{
    protected static ?string $model = RegistrationRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
    protected static ?string $navigationLabel = 'Registrations';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('select_customer')
                    ->label('Select Existing Customer (Autofill)')
                    ->options(fn () => Customer::pluck('name', 'id')->toArray())
                    ->placeholder('Search and select an existing customer...')
                    ->searchable()
                    ->dehydrated(false)
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $customer = Customer::find($state);
                            if ($customer) {
                                $set('clinic_name', $customer->name);
                                $set('contact_name', $customer->contact_name);
                                $set('email', $customer->contact_email);
                                $set('phone', $customer->contact_phone);
                            }
                        }
                    })
                    ->helperText('Select an existing customer to automatically fill their registration details.'),

                Forms\Components\TextInput::make('clinic_name')
                    ->label('Clinic / Hospital Name')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\TextInput::make('contact_name')
                    ->label('Contact Person')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\TextInput::make('email')
                    ->label('Contact Email')
                    ->required()
                    ->email()
                    ->maxLength(255),
                
                Forms\Components\TextInput::make('phone')
                    ->label('Contact Phone')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\TextInput::make('device_fingerprint')
                    ->label('Device Fingerprint (Hardware ID)')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending')
                    ->disabled()
                    ->dehydrated(true)
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Request Details')
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
                \Filament\Schemas\Components\Section::make('Device & Serial Key')
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
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contact_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('device_fingerprint')
                    ->label('Hardware ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('serialKey.key_value')
                    ->label('Generated Serial Key')
                    ->placeholder('N/A')
                    ->copyable()
                    ->copyMessage('Serial key copied')
                    ->copyMessageDuration(1500),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\Action::make('approve')
                    ->label('Approve & Generate Key')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        \Filament\Forms\Components\Select::make('duration')
                            ->label('License Duration')
                            ->options([
                                '30_days' => '30 Days',
                                '90_days' => '90 Days',
                                '180_days' => '180 Days',
                                '1_year' => '1 Year',
                                'lifetime' => 'Lifetime (No Expiry)',
                            ])
                            ->default('1_year')
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->hidden(fn (RegistrationRequest $record) => $record->status === 'rejected' || ($record->status === 'approved' && $record->serial_key_id !== null))
                    ->action(function (RegistrationRequest $record, array $data) {
                        // 1. Create/Find Customer Profile
                        $customer = Customer::firstOrCreate(
                            ['contact_email' => $record->email],
                            [
                                'name' => $record->clinic_name,
                                'contact_name' => $record->contact_name,
                                'contact_phone' => $record->phone,
                                'code' => 'CUST-' . Str::upper(Str::random(6)),
                                'max_devices' => 10,
                                'license_type' => 'hospital',
                                'status' => 'active',
                             ]
                        );

                        // 2. Generate a Serial Key for this customer
                        $serialKeyStr = collect(range(1, 4))
                            ->map(fn () => Str::upper(Str::random(4)))
                            ->join('-');

                        $expiresAt = match ($data['duration']) {
                            '30_days' => now()->addDays(30),
                            '90_days' => now()->addDays(90),
                            '180_days' => now()->addDays(180),
                            '1_year' => now()->addYear(),
                            'lifetime' => null,
                        };

                        $serialKey = SerialKey::create([
                            'customer_id' => $customer->id,
                            'key_value' => $serialKeyStr,
                            'hardware_fingerprint' => $record->device_fingerprint,
                            'max_activations' => 1,
                            'expires_at' => $expiresAt,
                            'status' => 'active',
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

                Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->hidden(fn (RegistrationRequest $record) => $record->status !== 'pending')
                    ->action(function (RegistrationRequest $record) {
                        $record->update([
                            'status' => 'rejected',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('Registration Rejected')
                            ->body("Registration for {$record->clinic_name} has been rejected.")
                            ->send();
                    }),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
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
