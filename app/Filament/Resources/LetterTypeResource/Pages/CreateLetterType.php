<?php

namespace App\Filament\Resources\LetterTypeResource\Pages;

use App\Filament\Resources\LetterTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLetterType extends CreateRecord
{
    protected static string $resource = LetterTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
