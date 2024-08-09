<?php

namespace App\Filament\Pages\Auth;

use App\Settings\MailSettings;
use Exception;
use Filament\Forms\Form;
use Filament\Facades\Filament;
use Filament\Forms\Components\Wizard;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Notifications\Auth\VerifyEmail;
use Filament\Pages\Auth\Register as BasePage;
use Illuminate\Contracts\Auth\MustVerifyEmail;


class Register extends BasePage
{
    protected function handleRegistration(array $data): Model
    {
        $user = $this->getUserModel()::create($data);
        $user->assignRole('user');

        return $user;
    }

    protected function getUsernameComponent(): Component
    {
        return TextInput::make('username')
            ->label('Username')
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getFirstNameComponent(): Component
    {
        return TextInput::make("firstname")
            ->label("First Name")
            ->required()
            ->autofocus();
    }

    protected function getLastNameComponent(): Component
    {
        return TextInput::make("lastname")
            ->label("Last Name")
            ->required()
            ->autofocus();
    }

    public function form(Form $form): Form
    {
        return $form->schema(([
            Wizard::make([
                Wizard\Step::make('data_diri')
                    ->label("Data Diri")
                    ->schema([
                        $this->getUsernameComponent(),
                        $this->getFirstNameComponent(),
                        $this->getLastNameComponent()
                    ]),
                Wizard\Step::make('akun')
                    ->label("Akun")
                    ->schema([
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
            ])
        ]));
    }

    public function getHeading(): string | Htmlable
    {
        return '';
    }

    protected function sendEmailVerificationNotification(Model $user): void
    {
        if (!$user instanceof MustVerifyEmail) {
            return;
        }

        if ($user->hasVerifiedEmail()) {
            return;
        }

        if (!method_exists($user, 'notify')) {
            $userClass = $user::class;

            throw new Exception("Model [{$userClass}] does not have a [notify()] method.");
        }

        $settings = app(MailSettings::class);

        $notification = new VerifyEmail();
        $notification->url = Filament::getVerifyEmailUrl($user);

        $settings->loadMailSettingsToConfig();

        $user->notify($notification);
    }
}
