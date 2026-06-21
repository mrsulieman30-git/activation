<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('code')
                    ->required(),
                TextInput::make('contact_name'),
                TextInput::make('contact_email')
                    ->email(),
                TextInput::make('contact_phone')
                    ->tel(),
                TextInput::make('hms_server_url')
                    ->url(),
                TextInput::make('hms_api_url')
                    ->url(),
                TextInput::make('max_devices')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('license_type')
                    ->required()
                    ->default('single'),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
