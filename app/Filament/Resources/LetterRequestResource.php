<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LetterRequestResource\Pages;
use App\Filament\Resources\LetterRequestResource\RelationManagers;
use App\Models\LetterRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LetterRequestResource extends Resource
{
    protected static ?string $model = LetterRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $pluralLabel = "Pengajuan Surat";
    protected static ?string $label = "Pengajuan Surat";

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
                Tables\Actions\EditAction::make(),
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
}
