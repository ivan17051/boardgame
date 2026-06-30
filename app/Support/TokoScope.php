<?php

namespace App\Support;

use App\Models\AdditionalItem;
use App\Models\RentalPromo;
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

        $idToko = self::userIdToko();

        return $query->where(function (Builder $q) use ($idToko) {
            $q->whereHas('meja', function (Builder $mq) use ($idToko) {
                $mq->where('id_toko', $idToko);
            })->orWhere(function (Builder $inner) use ($idToko) {
                $inner->whereNull('id_meja')
                    ->whereHas('additionalItems.additionalItem', function (Builder $aq) use ($idToko) {
                        $aq->where('id_toko', $idToko);
                    });
            });
        });
    }

    public static function scopeAdditionalItems(Builder $query): Builder
    {
        if (self::canSeeAll()) {
            return $query;
        }

        return $query->where('id_toko', self::userIdToko());
    }

    public static function scopeRentalPromos(Builder $query): Builder
    {
        if (self::canSeeAll()) {
            return $query;
        }

        return $query->where('id_toko', self::userIdToko());
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
        if (self::canSeeAll()) {
            return;
        }

        $rental->loadMissing('meja');
        if ($rental->meja) {
            self::authorizeMeja($rental->meja);

            return;
        }

        $rental->loadMissing('additionalItems.additionalItem');
        $idToko = self::userIdToko();
        $allowed = $rental->additionalItems->contains(function ($line) use ($idToko) {
            $master = $line->additionalItem;

            return $master && (int) $master->id_toko === $idToko;
        });

        if (! $allowed) {
            abort(404);
        }
    }

    public static function authorizeCashFlow(CashFlow $cashFlow): void
    {
        $cashFlow->loadMissing('rental.meja');
        if (! $cashFlow->rental || ! $cashFlow->rental->meja) {
            abort(404);
        }

        self::authorizeMeja($cashFlow->rental->meja);
    }

    public static function authorizeAdditionalItem(AdditionalItem $additionalItem): void
    {
        if (self::canSeeAll()) {
            return;
        }

        if ((int) $additionalItem->id_toko !== self::userIdToko()) {
            abort(403);
        }
    }

    public static function authorizeRentalPromo(RentalPromo $promo): void
    {
        if (self::canSeeAll()) {
            return;
        }

        if ((int) $promo->id_toko !== self::userIdToko()) {
            abort(403);
        }
    }
}
