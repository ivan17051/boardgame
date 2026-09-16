<?php

namespace App\Support;

use App\Models\Rental;
use App\Models\RentalMahjongHand;
use App\Models\RentalMahjongHandScore;
use App\Models\RentalMahjongPlayer;
use App\Models\RentalMahjongSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RentalMahjongScoring
{
    public const PLAYER_COUNT = 4;

    public static function scoreUrl(string $accessToken): string
    {
        return route('guest.mahjong-score.index', ['token' => $accessToken]);
    }

    /**
     * Ensure rental has guest_token and an open/closed score session with access_token.
     *
     * @return array{session: RentalMahjongSession, score_url: string, access_token: string}
     */
    public static function ensureScoreLink(Rental $rental): array
    {
        return DB::transaction(function () use ($rental) {
            $locked = Rental::query()->whereKey($rental->id)->lockForUpdate()->first();
            if (! $locked) {
                throw ValidationException::withMessages([
                    'rental' => ['Sewa tidak ditemukan.'],
                ]);
            }

            if (empty($locked->guest_token) && $locked->isActive()) {
                $locked->update(['guest_token' => Str::random(48)]);
                $locked->refresh();
            }

            $session = RentalMahjongSession::query()
                ->where('rental_id', $locked->id)
                ->lockForUpdate()
                ->first();

            if (! $session) {
                $session = RentalMahjongSession::query()->create([
                    'rental_id' => $locked->id,
                    'id_meja' => $locked->id_meja,
                    'status' => $locked->isActive() ? 'open' : 'closed',
                    'access_token' => Str::random(48),
                    'closed_at' => $locked->isActive() ? null : now(),
                ]);
            }

            return [
                'session' => $session->fresh(['players', 'hands.scores']),
                'score_url' => self::scoreUrl($session->access_token),
                'access_token' => $session->access_token,
            ];
        });
    }

    public static function findByAccessToken(string $token): ?RentalMahjongSession
    {
        if ($token === '') {
            return null;
        }

        return RentalMahjongSession::query()
            ->where('access_token', $token)
            ->with(['players', 'rental.meja.toko', 'hands' => function ($q) {
                $q->orderByDesc('hand_no')->with('scores');
            }])
            ->first();
    }

    public static function closeForRental(int $rentalId): void
    {
        RentalMahjongSession::query()
            ->where('rental_id', $rentalId)
            ->where('status', 'open')
            ->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);
    }

    /**
     * @param  array<int, array{seat?: int, nama: string, is_renter?: bool}>  $players
     */
    public static function setPlayers(RentalMahjongSession $session, array $players): RentalMahjongSession
    {
        self::assertWritable($session);

        if (count($players) !== self::PLAYER_COUNT) {
            throw ValidationException::withMessages([
                'players' => ['Harus mengisi tepat '.self::PLAYER_COUNT.' pemain.'],
            ]);
        }

        $hasHands = RentalMahjongHand::query()
            ->where('session_id', $session->id)
            ->whereNull('voided_at')
            ->exists();

        if ($hasHands) {
            throw ValidationException::withMessages([
                'players' => ['Nama pemain tidak bisa diubah setelah ada ronde yang tercatat.'],
            ]);
        }

        $normalized = [];
        $seatsSeen = [];
        foreach ($players as $index => $row) {
            $seat = isset($row['seat']) ? (int) $row['seat'] : ($index + 1);
            $nama = trim((string) ($row['nama'] ?? ''));
            if ($seat < 1 || $seat > self::PLAYER_COUNT) {
                throw ValidationException::withMessages([
                    'players' => ['Nomor kursi tidak valid.'],
                ]);
            }
            if ($nama === '') {
                throw ValidationException::withMessages([
                    'players' => ['Nama pemain kursi '.$seat.' wajib diisi.'],
                ]);
            }
            if (isset($seatsSeen[$seat])) {
                throw ValidationException::withMessages([
                    'players' => ['Kursi tidak boleh dobel.'],
                ]);
            }
            $seatsSeen[$seat] = true;
            $normalized[] = [
                'seat' => $seat,
                'nama' => mb_substr($nama, 0, 255),
                'is_renter' => ! empty($row['is_renter']),
            ];
        }

        if (count($seatsSeen) !== self::PLAYER_COUNT) {
            throw ValidationException::withMessages([
                'players' => ['Harus mengisi kursi 1 sampai '.self::PLAYER_COUNT.'.'],
            ]);
        }

        DB::transaction(function () use ($session, $normalized) {
            RentalMahjongPlayer::query()->where('session_id', $session->id)->delete();
            foreach ($normalized as $row) {
                RentalMahjongPlayer::query()->create([
                    'session_id' => $session->id,
                    'seat' => $row['seat'],
                    'nama' => $row['nama'],
                    'is_renter' => $row['is_renter'],
                ]);
            }
        });

        return $session->fresh(['players', 'rental.meja.toko', 'hands.scores']);
    }

    /**
     * @param  array<int, array{seat: int, poin: int}>  $scores
     */
    public static function appendHand(RentalMahjongSession $session, array $scores, ?int $winnerSeat = null): RentalMahjongSession
    {
        self::assertWritable($session);

        $players = $session->players()->orderBy('seat')->get();
        if ($players->count() !== self::PLAYER_COUNT) {
            throw ValidationException::withMessages([
                'scores' => ['Isi nama '.self::PLAYER_COUNT.' pemain terlebih dahulu.'],
            ]);
        }

        if (count($scores) !== self::PLAYER_COUNT) {
            throw ValidationException::withMessages([
                'scores' => ['Harus mengisi poin untuk '.self::PLAYER_COUNT.' pemain.'],
            ]);
        }

        $bySeat = [];
        foreach ($scores as $row) {
            $seat = (int) ($row['seat'] ?? 0);
            if ($seat < 1 || $seat > self::PLAYER_COUNT || isset($bySeat[$seat])) {
                throw ValidationException::withMessages([
                    'scores' => ['Data poin kursi tidak valid.'],
                ]);
            }
            if (! array_key_exists('poin', $row) || ! is_numeric($row['poin'])) {
                throw ValidationException::withMessages([
                    'scores' => ['Poin kursi '.$seat.' wajib angka.'],
                ]);
            }
            $bySeat[$seat] = (int) $row['poin'];
        }

        if (count($bySeat) !== self::PLAYER_COUNT) {
            throw ValidationException::withMessages([
                'scores' => ['Poin harus lengkap untuk semua kursi.'],
            ]);
        }

        if ($winnerSeat !== null && ($winnerSeat < 1 || $winnerSeat > self::PLAYER_COUNT)) {
            throw ValidationException::withMessages([
                'winner_seat' => ['Pemenang tidak valid.'],
            ]);
        }

        DB::transaction(function () use ($session, $players, $bySeat, $winnerSeat) {
            $nextNo = (int) RentalMahjongHand::query()
                ->where('session_id', $session->id)
                ->max('hand_no') + 1;

            $hand = RentalMahjongHand::query()->create([
                'session_id' => $session->id,
                'hand_no' => $nextNo,
                'winner_seat' => $winnerSeat,
                'created_at' => now(),
            ]);

            $playersBySeat = $players->keyBy('seat');
            foreach ($bySeat as $seat => $poin) {
                /** @var RentalMahjongPlayer $player */
                $player = $playersBySeat->get($seat);
                RentalMahjongHandScore::query()->create([
                    'hand_id' => $hand->id,
                    'player_id' => $player->id,
                    'poin' => $poin,
                ]);
            }
        });

        return $session->fresh(['players', 'rental.meja.toko', 'hands' => function ($q) {
            $q->orderByDesc('hand_no')->with('scores');
        }]);
    }

    public static function voidLastHand(RentalMahjongSession $session): RentalMahjongSession
    {
        self::assertWritable($session);

        $hand = RentalMahjongHand::query()
            ->where('session_id', $session->id)
            ->whereNull('voided_at')
            ->orderByDesc('hand_no')
            ->first();

        if (! $hand) {
            throw ValidationException::withMessages([
                'hand' => ['Tidak ada ronde yang bisa dibatalkan.'],
            ]);
        }

        $hand->update(['voided_at' => now()]);

        return $session->fresh(['players', 'rental.meja.toko', 'hands' => function ($q) {
            $q->orderByDesc('hand_no')->with('scores');
        }]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function payload(RentalMahjongSession $session): array
    {
        $session->loadMissing(['players', 'rental.meja.toko', 'hands.scores']);

        $players = $session->players->sortBy('seat')->values();
        $totals = [];
        foreach ($players as $player) {
            $totals[(int) $player->seat] = 0;
        }

        $handsOut = [];
        foreach ($session->hands->sortByDesc('hand_no') as $hand) {
            $scoreMap = [];
            foreach ($hand->scores as $score) {
                $player = $players->firstWhere('id', $score->player_id);
                if (! $player) {
                    continue;
                }
                $seat = (int) $player->seat;
                $scoreMap[$seat] = (int) $score->poin;
                if (! $hand->isVoided()) {
                    $totals[$seat] = ($totals[$seat] ?? 0) + (int) $score->poin;
                }
            }

            $handsOut[] = [
                'id' => (int) $hand->id,
                'hand_no' => (int) $hand->hand_no,
                'winner_seat' => $hand->winner_seat !== null ? (int) $hand->winner_seat : null,
                'voided' => $hand->isVoided(),
                'created_at' => $hand->created_at ? $hand->created_at->toIso8601String() : null,
                'scores' => collect(range(1, self::PLAYER_COUNT))->map(function ($seat) use ($scoreMap) {
                    return [
                        'seat' => $seat,
                        'poin' => array_key_exists($seat, $scoreMap) ? $scoreMap[$seat] : null,
                    ];
                })->all(),
            ];
        }

        $rental = $session->rental;
        $canWrite = $session->isOpen()
            && $rental
            && $rental->isActive();

        $playersReady = $players->count() === self::PLAYER_COUNT;
        $hasActiveHands = collect($handsOut)->contains(function ($hand) {
            return empty($hand['voided']);
        });

        return [
            'session' => [
                'id' => (int) $session->id,
                'status' => $session->status,
                'can_write' => $canWrite,
                'players_ready' => $playersReady,
                'players_locked' => $hasActiveHands,
                'closed_at' => $session->closed_at ? $session->closed_at->toIso8601String() : null,
            ],
            'rental' => [
                'id' => $rental ? (int) $rental->id : null,
                'status' => $rental ? $rental->status : null,
                'nama_customer' => $rental ? $rental->nama_customer : null,
                'nama_meja' => ($rental && $rental->meja) ? $rental->meja->nama : null,
                'nama_toko' => ($rental && $rental->meja && $rental->meja->toko)
                    ? $rental->meja->toko->nama
                    : null,
            ],
            'players' => $players->map(function (RentalMahjongPlayer $player) use ($totals) {
                $seat = (int) $player->seat;

                return [
                    'id' => (int) $player->id,
                    'seat' => $seat,
                    'nama' => $player->nama,
                    'is_renter' => (bool) $player->is_renter,
                    'total' => (int) ($totals[$seat] ?? 0),
                ];
            })->values()->all(),
            'hands' => $handsOut,
            'player_count' => self::PLAYER_COUNT,
        ];
    }

    private static function assertWritable(RentalMahjongSession $session): void
    {
        $session->loadMissing('rental');

        if (! $session->isOpen()) {
            throw ValidationException::withMessages([
                'session' => ['Sesi skor sudah ditutup.'],
            ]);
        }

        $rental = $session->rental;
        if (! $rental || ! $rental->isActive()) {
            throw ValidationException::withMessages([
                'session' => ['Sewa sudah tidak aktif. Skor hanya bisa dilihat.'],
            ]);
        }
    }
}
