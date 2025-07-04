<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BasePage;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BasePage
{
    protected static string $view = 'filament.pages.auth.login';
    protected static string $layout = 'components/layouts/simple';

    public function mount(): void
    {
        parent::mount();

        // $this->form->fill([
        //     'email' => 'superadmin@gmail.com',
        //     'password' => 'superadmin',
        // ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getEmailFormComponent()->label('Email'),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }

    public function getHeading(): string | Htmlable
    {
        return '';
    }
}
