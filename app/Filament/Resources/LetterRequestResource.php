<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Enums\Status;
use App\Models\Letter;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\LetterRequest;
use App\Services\LetterService;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\LetterRequestResource\Pages;
use Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction;
use App\Filament\Resources\LetterRequestResource\RelationManagers;

class LetterRequestResource extends Resource
{
    protected static ?string $model = LetterRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // protected static ?string $pluralLabel = $this->getTitleByUser();
    // protected static ?string $label = $this->getTitleByUser();

    public static function getTitleByUser(string $role): string {
       return $role === 'user' ? "Pengajuan Surat" : "Surat Masuk";
    }

    public static function getPluralLabel(): ?string
    {
        $roleName = Auth::user()->roles()->first()->name;
        return self::getTitleByUser($roleName);
    }

    public static function getLabel(): ?string
    {
        $roleName = Auth::user()->roles()->first()->name;
        return self::getTitleByUser($roleName);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make("Pengajuan Surat")
                    ->schema([
                        Forms\Components\Select::make("letter_type_id")
                            ->label("Jenis Surat")
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->required()
                            ->relationship(name: "letterType", titleAttribute: "name"),

                    ]),

                Forms\Components\Section::make("Data Diri")
                    ->description("data diri pengaju surat")
                    ->columns([
                        'md' => 2
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label("Nama")
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('id_number')
                            ->label("NIK")
                            ->length(16)
                            ->required(),
                        Forms\Components\TextInput::make('birthplace_id')
                            ->label("Tempat Lahir")
                            ->required(),
                        Forms\Components\DatePicker::make('birth_date')
                            ->label("Tanggal Lahir")
                            ->native(false)
                            ->required(),
                        Forms\Components\Select::make('gender')
                            ->label("Jenis Kelamin")
                            ->options([
                                'perempuan' => "Perempuan",
                                "laki-laki" => "Laki-laki"
                            ])
                            ->native(false)
                            ->required(),
                        Forms\Components\Select::make('religion')
                            ->label("Agama")
                            ->options([
                                "islam" => "Islam",
                                "protestan" => "Kristen Protestan",
                                "katolik" => "Kristen Katolik",
                                "hindu" => "Hindu",
                                "buddha" => "Buddha",
                                "konghucu" => "Konghucu"
                            ])
                            ->native(false)
                            ->required(),
                        Forms\Components\TextInput::make('work')
                            ->label("Pekerjaan")
                            ->required(),
                        Forms\Components\Textarea::make('address')
                            ->label("Alamat")
                            ->required(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label("Nama")
                    ->searchable(),
                Tables\Columns\TextColumn::make('gender')
                    ->label("Jenis Kelamin")
                    ->searchable(),
                Tables\Columns\TextColumn::make('work')
                    ->label("Pekerjaan")
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->label("Alamat")
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Proses' => 'gray',
                        'Disposisi' => 'warning',
                        'Selesai' => 'success',
                    })
                    ->searchable(),
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
                // Tables\Actions\ViewAction::make(),
                // MediaAction::make("show")
                //     ->label("Lihat")
                //     ->icon("heroicon-o-eye")
                //     ->media(function (LetterService $letterService, LetterRequest $record) {
                //         $letter = Letter::where('letter_request_id', $record->id)->firstOrFail();
                //         // Generate PDF dari template
                //         $pdfPath = $letterService->generatePDFFromTemplate($letter);

                //         // Redirect ke URL file PDF untuk membuka di tab baru
                //         return $pdfPath;
                //     })
                //     ->disabled(function (LetterRequest $record) {
                //         return $record->status == Status::PROSES->value;
                //     }),
                Tables\Actions\Action::make("lihat")
                    ->label("Lihat Surat")
                    ->icon("heroicon-o-document")
                    ->color("success")
                    ->url(function (LetterService $letterService, LetterRequest $record) {
                        if($record->status != Status::PROSES->value) {
                            $letter = Letter::where('letter_request_id', $record->id)->first();
                            // Generate PDF dari template
                            $pdfPath = $letterService->generatePDFFromTemplate($letter);

                            // Redirect ke URL file PDF untuk membuka di tab baru
                            return $pdfPath;
                        }
                    })
                    ->openUrlInNewTab()
                    ->disabled(function (LetterRequest $record) {
                        return $record->status == Status::PROSES->value;
                    }),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()->visible(function (LetterRequest $record) {
                        return $record->status == Status::PROSES->value;
                    }),
                    Tables\Actions\Action::make("disposisi")
                        ->requiresConfirmation()
                        ->modalHeading("Disposisi Surat")
                        ->modalDescription("Tindakan ini akan mengirim surat ke Kepala Desa untuk ditanda tangani")
                        ->icon("heroicon-o-arrow-uturn-right")
                        ->action(function (LetterRequest $record) {
                            $record->disposisi_action();

                            $record->create_reply();

                            Notification::make()
                                ->success()
                                ->title("Pengajuan didisposisi")
                                ->send();
                        })
                        ->hidden(function (LetterRequest $record) {
                            // hidden for user
                            if (auth()->user()->roles[0]->name == "user") {
                                return true;
                            }

                            if ($record->status == Status::DISPOSISI->value) {
                                return true;
                            }

                            return false;
                        })
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
            'index' => Pages\ListLetterRequests::route('/'),
            'create' => Pages\CreateLetterRequest::route('/create'),
            'edit' => Pages\EditLetterRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = User::find(auth()->user()->id);
        $isUSer = $user->roles[0]->name == "user";

        if ($isUSer) {
            return parent::getEloquentQuery()->where('created_by', $user->id);
        }

        return parent::getEloquentQuery();
    }
}
