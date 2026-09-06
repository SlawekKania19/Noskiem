<?php

namespace App\Filament\Pages;

use App\Filament\Support\AuthorProfileFields;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

// ---------------------------
// Strona „Edytuj profil" w panelu (menu w prawym górnym rogu). Rozszerza wbudowaną
// stronę Filamenta o sekcję „Profil autora (blog)" — widoczną wyłącznie dla kont
// z rolą is_author. Dzięki temu autor sam uzupełnia swój profil, bez pomocy admina.
// Rejestracja: AdminPanelProvider::panel()->profile(EditProfile::class).
// ---------------------------

class EditProfile extends BaseEditProfile
{
    // ** Domyślnie strona profilu jest wąska (jak logowanie) — poszerzamy, bo doszły
    // pola profilu autora z edytorami WYSIWYG, którym ciasno w wąskiej kolumnie
    protected Width|string|null $maxWidth = Width::FiveExtraLarge;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getNameFormComponent(),
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
            $this->getCurrentPasswordFormComponent(),

            AuthorProfileFields::section(fn () => (bool) auth()->user()?->is_author),
        ]);
    }
}
