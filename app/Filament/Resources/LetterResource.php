<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Letter;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use App\Services\LetterService;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\LetterResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\LetterResource\RelationManagers;
use Filament\Support\Enums\Alignment;
use Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction;

class LetterResource extends Resource
{
    protected static ?string $model = Letter::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $pluralLabel = "Surat Keluar";
    protected static ?string $label = "Surat Keluar";

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('letter_request_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('number')
                    ->required()
                    ->numeric(),
                Forms\Components\DateTimePicker::make('sign_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('letter_request.name')
                    ->label("Untuk")
                    ->sortable(),
                Tables\Columns\TextColumn::make('letter_request.letterType.name')
                    ->label("Surat")
                    ->sortable(),
                Tables\Columns\TextColumn::make('letter_request.status')
                    ->label("Status")
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Proses' => 'gray',
                        'Disposisi' => 'warning',
                        'Selesai' => 'success',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('number')
                    ->label("Nomor Surat")
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('sign_at')
                    ->label('Tanda Tangan')
                    ->boolean() // Gunakan boolean untuk icon ceklis/silang
                    ->trueIcon('heroicon-o-check-circle') // Ikon untuk kondisi signed
                    ->falseIcon('heroicon-o-x-circle') // Ikon untuk kondisi belum signed
                    ->getStateUsing(function ($record) {
                        return !is_null($record->sign_at);
                    })

                    ->sortable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make("ttd")
                    ->label("Tanda Tangan")
                    ->hidden(function () {
                        $user = User::find(auth()->user()->id);
                        $isKades = $user->roles()->first()->name == 'kades';
                        if (!$isKades) return true;

                        return false;
                    })
                    ->disabled(function (Letter $record){
                        if($record->sign_at) return true;
                        return false;
                    })
                    ->form([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required()
                    ])
                    ->action(function (array $data, Letter $record) {
                        $user = auth()->user();

                        if (!Hash::check($data['password'], $user->password)) {
                            return Notification::make()
                                ->title("Error")
                                ->body("Password Salah")
                                ->danger()
                                ->send();
                        }

                        $record->sign_letter();

                        return Notification::make()
                            ->title("Berhasil")
                            ->body("Surat ditanda tangani!")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalDescription("Masukan Password akun anda untuk menanda tangani surat")
                    ->icon('heroicon-o-shield-check'),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                    MediaAction::make('show-pdf')
                        ->label("Lihat Surat")
                        ->modalHeading("test")
                        ->icon("heroicon-o-document")
                        ->color("success")
                        ->modalFooterActionsAlignment(Alignment::Center)
                        ->media(function(LetterService $letterService){
                            $data = [
                                'nama' => "Firdaus",
                                'tempat_lahir' => "Makassar",
                                'tanggal_lahir' => "00 00 0000"
                            ];

                            $templateName = "keteragan_tidak_mampu";
                            // Generate PDF dari template
                            $pdfPath = $letterService->generatePDFFromTemplate($templateName, $data);

                            // Redirect ke URL file PDF untuk membuka di tab baru
                            return $pdfPath;
                        })
                        ->extraModalFooterActions([
                            Tables\Actions\Action::make('open-url')
                                ->label('Open in browser')
                                ->url("#")
                                ->openUrlInNewTab()
                                ->icon('heroicon-o-globe-alt')
                            ]),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListLetters::route('/'),
            'create' => Pages\CreateLetter::route('/create'),
            'edit' => Pages\EditLetter::route('/{record}/edit'),
        ];
    }

    // public static function getEloquentQuery(): Builder
    // {
    //     $user = User::find(auth()->user()->id);
    //     $isUSer = $user->roles[0]->name == "user";

    //     if($isUSer){
    //         return parent::getEloquentQuery()->where('created_by', $user->id);
    //     }

    //     return parent::getEloquentQuery();
    // }
}
