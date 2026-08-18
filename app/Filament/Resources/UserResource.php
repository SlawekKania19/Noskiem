<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RestrictedToAdmin;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;

// ---------------------------
// Zarządzanie kontami w panelu — Admin / Moderator / Autor, dowolna kombinacja
// (to trzy niezależne flagi, nie wykluczające się role). Tylko dla Admina.
// ---------------------------
class UserResource extends Resource
{
    use RestrictedToAdmin;

    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Użytkownicy';
    protected static ?string $modelLabel = 'użytkownik';
    protected static ?string $pluralModelLabel = 'użytkownicy';
    protected static ?int $navigationSort = 99;

    // Nadpisuje canDelete() z RestrictedToAdmin — oprócz bycia Adminem, nie można
    // usunąć samego siebie (samo ukrycie przycisku w tabeli to za mało, to jest
    // realna autoryzacja akcji, nie tylko UI)
    public static function canDelete($record): bool
    {
        return static::canViewAny() && $record->id !== auth()->id();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('name')->label('Imię i nazwisko')->required()->maxLength(255),
                TextInput::make('email')->label('E-mail')->email()->required()->unique(ignoreRecord: true)->maxLength(255),
                TextInput::make('password')
                    ->label('Hasło')
                    ->password()
                    ->revealable()
                    ->required()
                    ->rule(Password::default())
                    ->visible(fn (string $operation) => $operation === 'create')
                    ->helperText('Hasło zmienisz później akcją "Resetuj hasło" na liście.'),
            ]),
            Section::make('Rola')->schema([
                Toggle::make('is_admin')
                    ->label('Administrator')
                    ->helperText('Pełny dostęp do panelu.')
                    ->disabled(fn (?User $record) => $record?->id === auth()->id()),
                Toggle::make('is_moderator')
                    ->label('Moderator')
                    ->helperText('Dostęp do moderacji zgłoszeń.'),
                Toggle::make('is_author')
                    ->label('Autor')
                    ->helperText('Docelowo: pisanie artykułów (funkcja jeszcze niedostępna).'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Imię i nazwisko')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->label('E-mail')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('roles')
                    ->label('Rola')
                    ->state(function (User $record) {
                        $roles = array_filter([
                            $record->is_admin ? 'Admin' : null,
                            $record->is_moderator ? 'Moderator' : null,
                            $record->is_author ? 'Autor' : null,
                        ]);

                        return $roles ?: ['Brak roli'];
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Admin' => 'danger',
                        'Moderator' => 'warning',
                        'Autor' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Utworzono')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('name')
            ->actions([
                Actions\EditAction::make(),

                // ** Osobna, prosta akcja zamiast pola hasła w formularzu edycji —
                // admin resetuje hasło bez otwierania pełnego formularza edycji konta
                Actions\Action::make('resetPassword')
                    ->label('Resetuj hasło')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->form([
                        TextInput::make('password')
                            ->label('Nowe hasło')
                            ->password()
                            ->revealable()
                            ->required()
                            ->rule(Password::default())
                            ->confirmed(),
                        TextInput::make('password_confirmation')
                            ->label('Powtórz nowe hasło')
                            ->password()
                            ->revealable()
                            ->required(),
                    ])
                    ->action(function (User $record, array $data) {
                        $record->update(['password' => $data['password']]);

                        Notification::make()->title('Hasło zmienione')->success()->send();
                    }),

                Actions\DeleteAction::make()
                    ->visible(fn (User $record) => $record->id !== auth()->id()),
            ]);
    }

    public static function getRelationManagers(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
