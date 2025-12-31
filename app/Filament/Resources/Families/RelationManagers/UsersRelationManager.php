<?php

namespace App\Filament\Resources\Families\RelationManagers;

use App\Enums\ProfileUserEnum;
use App\Enums\RoleUserEnum;
use App\Enums\UserStatusEnum;
use App\Exceptions\EmailAlreadyExistsException;
use App\Filament\Actions\SimpleActions;
use App\Filament\Resources\Families\Schemas\FamilyMemberForm;
use App\Models\Family;
use App\Models\User;
use App\Services\UserService;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use STS\FilamentImpersonate\Actions\Impersonate;
use Throwable;

class UsersRelationManager extends RelationManager
{

    protected static string $relationship = 'users';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $title = 'Membros';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->required()
                            ->maxLength(255)
                            ->email()
                            ->visible(fn (string $operation): bool => $operation === 'create'),
                        Select::make('type')
                            ->label('Perfil')
                            ->required()
                            ->options(ProfileUserEnum::class)
                            ->default(ProfileUserEnum::ROLE_MEMBER)
                            ->disableOptionWhen(fn (string $value): bool => in_array($value, [
                                ProfileUserEnum::ROLE_SUPER_ADMIN->value
                            ]))
                            ->visible(fn (string $operation): bool => $operation === 'create'),
                        Radio::make('status')
                            ->label('Status')
                            ->inline()
                            ->required()
                            ->options(UserStatusEnum::class)
                            ->default(UserStatusEnum::ATI)
                            ->disabled(function ($get, $record, string $operation): bool {

                                if ($operation === 'edit') {
                                    return in_array($record->profile, [
                                        ProfileUserEnum::ROLE_SUPER_ADMIN,
                                        ProfileUserEnum::ROLE_ADMIN,
                                    ]);
                                }

                                return false;
                            })
                    ])
                    ->columnSpanFull()
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('family_id')
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('')
                    ->disk('public') // 🚨 CHAVE 1: Define o disco onde a imagem está salva
                    ->visibility('public')
                    ->width(40)
                    ->imageHeight(40)
                    ->circular(),
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
//                TextColumn::make('profile')
//                    ->label('Perfil')
//                    ->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make()
                    ->icon(Iconoir::PlusCircle)
                    ->label('Novo')
                    ->size(Size::ExtraLarge)
                    ->button()
                    ->color('primary')
                    ->modalHeading('Novo membro')
                    ->createAnother(false)
                    ->modalSubmitActionLabel('Cadastrar')
                    ->action(function (array $data, \Filament\Actions\Action $action) {
                        /* @var Family $activeFamily */
                        $activeFamily = filament()->getTenant();

                        if (!$activeFamily) {
                            Notification::make()
                                ->title('Erro de Ambiente')
                                ->body(__('custom.notification.family_not_found'))
                                ->danger()
                                ->send();
                            $action->halt();
                        }

                        try {
                            UserService::create($data, $activeFamily);

                            Notification::make('createSuccess')
                                ->title('Usuário criado com sucesso!')
                                ->success()
                                ->send();

                        } catch (EmailAlreadyExistsException $e) {
                            Notification::make('emailExist')
                                ->title(__('custom.notification.email_duplicate'))
                                ->warning()
                                ->send();

                            $action->halt();
                        } catch (Throwable $e) {
                            Notification::make('createCategoryError')
                                ->title('Erro ao cadastrar!')
                                ->body('Detalhe: ' . $e->getMessage())
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    })
                    ->modalWidth(Width::Small),
            ])
            ->recordClasses(function (User $record): ?string {
                $user = $record->refresh();
                $isAdmin = $user->isSuperAdmin() || $user->isAdmin();
                return $isAdmin ? 'category-row-default' : null;
            })
            ->recordActions([
                SimpleActions::getViewWithEditAndDelete(
                    width         : Width::Large,
                    schemaCallback: fn (Schema $schema) => FamilyMemberForm::configure($schema),
                    actionCallback: function (array $data, User $record, Action $action): void {

                        $success = UserService::update($data, $record);

                        if ($success) {
                            Notification::make()
                                ->title('Membro atualizado com sucesso!')
                                ->success()
                                ->send();
                            $action->cancel();
                        }
                    }
                )
                    ->visible(function (User $record): bool {
                        $user = $record->refresh();
                        return $user->isSuperAdmin() || $user->isAdmin();
                    }),
                Impersonate::make()
                    ->size(Size::ExtraLarge)
                    ->color(Color::Blue)
                    ->label('')
                    ->button()
            ])
            ->toolbarActions([])
            ->modifyQueryUsing(function (Builder $query) {
                if (auth()->user()->isSuperAdmin()) {
                    $query->where('family_user.role', '!=', RoleUserEnum::ROLE_SUPER_ADMIN->value);
                }
            });
    }
}
