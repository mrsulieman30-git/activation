<?php

namespace App\Filament\Resources\ActivationRequests;

use App\Filament\Resources\ActivationRequests\Pages\CreateActivationRequest;
use App\Filament\Resources\ActivationRequests\Pages\EditActivationRequest;
use App\Filament\Resources\ActivationRequests\Pages\ListActivationRequests;
use App\Filament\Resources\ActivationRequests\Pages\ViewActivationRequest;
use App\Filament\Resources\ActivationRequests\Schemas\ActivationRequestForm;
use App\Filament\Resources\ActivationRequests\Schemas\ActivationRequestInfolist;
use App\Filament\Resources\ActivationRequests\Tables\ActivationRequestsTable;
use App\Models\ActivationRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ActivationRequestResource extends Resource
{
    protected static ?string $model = ActivationRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'request_id';

    public static function form(Schema $schema): Schema
    {
        return ActivationRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ActivationRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActivationRequestsTable::configure($table);
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
            'index' => ListActivationRequests::route('/'),
            'create' => CreateActivationRequest::route('/create'),
            'view' => ViewActivationRequest::route('/{record}'),
            'edit' => EditActivationRequest::route('/{record}/edit'),
        ];
    }
}
