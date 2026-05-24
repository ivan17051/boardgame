<?php

namespace App\Support;

use App\Models\CashFlow;
use App\Models\Meja;
use App\Models\Rental;
use App\Models\Toko;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TokoScope
{
    public static function userIdToko(): int
    {
        $user = auth()->user();

        return $user ? (int) $user->id_toko : 0;
    }

    public static function canSeeAll(): bool
    {
        return self::userIdToko() === 0;
    }

    public static function scopeUsers(Builder $query): Builder
    {
        if (self::canSeeAll()) {
            return $query;
        }

        return $query->where('id_toko', self::userIdToko());
    }

    public static function scopeTokos(Builder $query): Builder
    {
        if (self::canSeeAll()) {
            return $query;
        }

        return $query->whereKey(self::userIdToko());
    }

    public static function scopeMejas(Builder $query): Builder
    {
        if (self::canSeeAll()) {
            return $query;
        }

        return $query->where('id_toko', self::userIdToko());
    }

    public static function scopeCashFlows(Builder $query): Builder
    {
        if (self::canSeeAll()) {
            return $query;
        }

        return $query->whereHas('rental.meja', function (Builder $q) {
            $q->where('id_toko', self::userIdToko());
        });
    }

    public static function scopeRentals(Builder $query): Builder
    {
        if (self::canSeeAll()) {
            return $query;
        }

        return $query->whereHas('meja', function (Builder $q) {
            $q->where('id_toko', self::userIdToko());
        });
    }

    public static function resolveIdTokoForSave($requested): int
    {
        if (self::canSeeAll()) {
            return (int) $requested;
        }

        return self::userIdToko();
    }

    public static function authorizeUser(User $user): void
    {
        if (self::canSeeAll()) {
            return;
        }

        if ((int) $user->id_toko !== self::userIdToko()) {
            abort(403);
        }
    }

    public static function authorizeToko(Toko $toko): void
    {
        if (self::canSeeAll()) {
            return;
        }

        if ((int) $toko->id !== self::userIdToko()) {
            abort(403);
        }
    }

    public static function authorizeMeja(Meja $meja): void
    {
        if (self::canSeeAll()) {
            return;
        }

        if ((int) $meja->id_toko !== self::userIdToko()) {
            abort(403);
        }
    }

    public static function authorizeRental(Rental $rental): void
    {
        $rental->loadMissing('meja');
        if (! $rental->meja) {
            abort(404);
        }

        self::authorizeMeja($rental->meja);
    }

    public static function authorizeCashFlow(CashFlow $cashFlow): void
    {
        $cashFlow->loadMissing('rental.meja');
        if (! $cashFlow->rental || ! $cashFlow->rental->meja) {
            abort(404);
        }

        self::authorizeMeja($cashFlow->rental->meja);
    }
}
