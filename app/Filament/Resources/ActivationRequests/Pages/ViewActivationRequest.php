<?php

namespace App\Filament\Resources\ActivationRequests\Pages;

use App\Filament\Resources\ActivationRequests\ActivationRequestResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewActivationRequest extends ViewRecord
{
    protected static string $resource = ActivationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
