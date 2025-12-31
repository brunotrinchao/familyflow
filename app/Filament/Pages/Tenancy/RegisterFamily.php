<?php

namespace App\Filament\Pages\Tenancy;

use App\Enums\ProfileUserEnum;
use App\Models\Family;
use App\Services\FamilyService;
use Filament\Auth\Pages\Register;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class RegisterFamily extends Register
{

    public static function getLabel(): string
    {
        return 'Criar conta';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Seu nome completo')
                    ->required()
                    ->maxLength(255),
                TextInput::make('family_name')
                    ->label('Nome da familia')
                    ->required()
                    ->maxLength(30)
                    ->helperText('Ex.: Silva Santos, Oliveira Araujo Souza')
                    ->unique(table: 'families', column: 'name'),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function handleRegistration(array $data): \Illuminate\Database\Eloquent\Model
    {

        $user = parent::handleRegistration([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
            'profile'  => ProfileUserEnum::ROLE_ADMIN
        ]);

        $data['slug'] = Str::slug($data['family_name']);


        $family = (new \App\Services\FamilyService)->create($data);

        $user->families()->attach($family);

        return $user;
    }
}
