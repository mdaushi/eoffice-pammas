<?php

namespace App\Filament\Resources\LetterRequestResource\Pages;

use App\Enums\Status;
use Filament\Actions;
use Illuminate\Support\Str;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\LetterRequestResource;

class CreateLetterRequest extends CreateRecord
{
    protected static string $resource = LetterRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['unique_field'] = Str::random(10);
        $data['created_by'] = auth()->user()->id;
        $data["status"] = Status::PROSES;
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
