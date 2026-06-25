<?php

namespace App\Filament\Resources\ActivationRequests\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class ActivationRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('request_id')
                    ->required()
                    ->disabled(),
                Select::make('serial_key_id')
                    ->relationship('serialKey', 'key_value')
                    ->required()
                    ->disabled(),
                TextInput::make('device_fingerprint')
                    ->required()
                    ->disabled(),
                TextInput::make('device_name')
                    ->disabled(),
                TextInput::make('app_version')
                    ->disabled(),
                TextInput::make('os_version')
                    ->disabled(),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'revoked' => 'Revoked',
                    ])
                    ->required()
                    ->default('pending'),
                DateTimePicker::make('reviewed_at')
                    ->disabled(),
                Textarea::make('rejection_reason')
                    ->columnSpanFull(),
            ]);
    }
}
