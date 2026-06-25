<?php

namespace App\Filament\Resources\ActivationRequests\Pages;

use App\Filament\Resources\ActivationRequests\ActivationRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditActivationRequest extends EditRecord
{
    protected static string $resource = ActivationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
