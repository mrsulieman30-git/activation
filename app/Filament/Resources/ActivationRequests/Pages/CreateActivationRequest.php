<?php

namespace App\Filament\Resources\ActivationRequests\Pages;

use App\Filament\Resources\ActivationRequests\ActivationRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateActivationRequest extends CreateRecord
{
    protected static string $resource = ActivationRequestResource::class;
}
