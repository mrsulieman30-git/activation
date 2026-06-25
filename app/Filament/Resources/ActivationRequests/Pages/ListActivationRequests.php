<?php

namespace App\Filament\Resources\ActivationRequests\Pages;

use App\Filament\Resources\ActivationRequests\ActivationRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListActivationRequests extends ListRecords
{
    protected static string $resource = ActivationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
