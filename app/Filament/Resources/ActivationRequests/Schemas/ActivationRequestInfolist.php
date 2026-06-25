<?php

namespace App\Filament\Resources\ActivationRequests\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ActivationRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('request_id'),
                TextEntry::make('serial_key_id')
                    ->numeric(),
                TextEntry::make('device_fingerprint'),
                TextEntry::make('device_name')
                    ->placeholder('-'),
                TextEntry::make('app_version')
                    ->placeholder('-'),
                TextEntry::make('os_version')
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('reviewed_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('reviewed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('rejection_reason')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
