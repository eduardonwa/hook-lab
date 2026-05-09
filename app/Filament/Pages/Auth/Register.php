<?php

namespace App\Filament\Pages\Auth;

use App\Mail\WelcomeUserMail;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class Register extends BaseRegister
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Correo electrónico')
            ->email()
            ->required()
            ->maxLength(255)
            ->rules([
                'email:rfc,dns',
            ])
            ->unique($this->getUserModel());
    }
    
    protected function handleRegistration(array $data): Model
    {
        $user = parent::handleRegistration($data);

        Mail::to($user->email)->send(new WelcomeUserMail($user));

        return $user;
    }
}