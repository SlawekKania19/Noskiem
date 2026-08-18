<?php

namespace App\Filament\Concerns;

// ---------------------------
// Ogranicza widoczność/edycję zasobu wyłącznie do Admina. Reszta ról (Moderator,
// Autor) na razie nie ma tu nic do roboty — moderacja ma własną, osobną regułę
// w AnimalEditResource (Admin lub Moderator).
// ---------------------------
trait RestrictedToAdmin
{
    public static function canViewAny(): bool
    {
        return auth()->user()?->is_admin ?? false;
    }

    public static function canView($record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return static::canViewAny();
    }

    public static function canDeleteAny(): bool
    {
        return static::canViewAny();
    }
}
