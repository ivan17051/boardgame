<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BornpadelMahjongTournaments
{
    /**
     * @return array{items: array<int, array<string, mixed>>, error: string|null}
     */
    public static function fetch(?string $status = null): array
    {
        $fromDatabase = self::fetchFromDatabase($status);
        if ($fromDatabase['error'] === null) {
            return $fromDatabase;
        }

        return self::fetchFromApi($status);
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, error: string|null}
     */
    private static function fetchFromDatabase(?string $status): array
    {
        try {
            $connection = DB::connection('bornpadel');

            if (! Schema::connection('bornpadel')->hasTable('m_turnamen')
                || ! Schema::connection('bornpadel')->hasColumn('m_turnamen', 'jenis')) {
                return [
                    'items' => [],
                    'error' => 'Database Bornpadel belum memiliki tabel turnamen yang lengkap.',
                ];
            }

            $query = $connection->table('m_turnamen')
                ->where('jenis', 'mahjong')
                ->orderByDesc('tanggal')
                ->orderByDesc('id');

            if ($status !== null && $status !== '') {
                $query->where('status', $status);
            }

            $items = $query->get()->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'nama' => $row->nama,
                    'tanggal' => $row->tanggal ?? null,
                    'harga' => $row->harga ?? 0,
                    'syarat' => $row->syarat ?? null,
                    'jenis' => $row->jenis ?? 'mahjong',
                    'jenis_label' => 'Mahjong',
                    'status' => $row->status ?? null,
                    'mahjong_is_final' => (bool) ($row->mahjong_is_final ?? false),
                    'registration_open' => ($row->status ?? null) === 'open',
                ];
            })->values()->all();

            return [
                'items' => $items,
                'error' => null,
            ];
        } catch (Throwable $e) {
            return [
                'items' => [],
                'error' => 'Database Bornpadel: '.$e->getMessage(),
            ];
        }
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, error: string|null}
     */
    private static function fetchFromApi(?string $status): array
    {
        $apiUrl = rtrim((string) config('services.bornpadel.api_url'), '/');
        $token = config('services.bornpadel.api_token');

        if (! $token || $apiUrl === '') {
            return [
                'items' => [],
                'error' => 'Token atau URL API Bornpadel belum dikonfigurasi.',
            ];
        }

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->withToken($token)
                ->get($apiUrl.'/tournaments/mahjong', array_filter([
                    'status' => $status,
                ]));

            if ($response->successful() && $response->json('success') === true) {
                return [
                    'items' => $response->json('data') ?? [],
                    'error' => null,
                ];
            }

            return [
                'items' => [],
                'error' => $response->json('message') ?? 'Gagal memuat data turnamen dari API.',
            ];
        } catch (Throwable $e) {
            return [
                'items' => [],
                'error' => 'Tidak dapat terhubung ke server turnamen.',
            ];
        }
    }

    /**
     * @return array{data: array<string, mixed>|null, error: string|null}
     */
    public static function fetchGroupStandings(int $id): array
    {
        $fromDatabase = self::fetchGroupStandingsFromDatabase($id);
        if ($fromDatabase['error'] === null) {
            return $fromDatabase;
        }

        return self::fetchGroupStandingsFromApi($id);
    }

    /**
     * @return array{data: array<string, mixed>|null, error: string|null}
     */
    private static function fetchGroupStandingsFromDatabase(int $id): array
    {
        try {
            $connection = DB::connection('bornpadel');

            if (! Schema::connection('bornpadel')->hasTable('m_turnamen')
                || ! Schema::connection('bornpadel')->hasTable('grup')
                || ! Schema::connection('bornpadel')->hasTable('grup_member')) {
                return [
                    'data' => null,
                    'error' => 'Database Bornpadel belum memiliki tabel klasemen.',
                ];
            }

            $turnamen = $connection->table('m_turnamen')
                ->where('id', $id)
                ->where('jenis', 'mahjong')
                ->first();

            if (! $turnamen) {
                return [
                    'data' => null,
                    'error' => 'Turnamen tidak ditemukan.',
                ];
            }

            $babakNumbers = $connection->table('grup')
                ->where('id_turnamen', $id)
                ->distinct()
                ->orderBy('babak')
                ->pluck('babak');

            $sections = [];

            foreach ($babakNumbers as $babak) {
                $babak = (int) $babak;
                $groups = self::resolveMahjongGrupBatchForBabak($connection, $id, $babak);
                $groupPayload = [];

                foreach ($groups as $group) {
                    $members = self::orderedGroupMembers($connection, $group->id);
                    $standings = [];
                    $rank = 1;

                    foreach ($members as $member) {
                        $poinBabak = self::resolveMahjongBabakPoints($connection, $member, $babak, $id, (bool) $group->is_aktif);
                        $totalPoin = self::resolveMahjongTotalPoints($member, $poinBabak, (bool) $group->is_aktif);
                        $nama = self::resolveMemberDisplayName($connection, $member);

                        $standings[] = [
                            'rank' => $rank,
                            'id_pemain' => (int) $member->id_pemain,
                            'id_peserta' => (int) $member->id_turnamen_peserta,
                            'pemain_ids' => self::resolveStandingPemainIds($connection, $member),
                            'nama' => $nama,
                            'grup_nama' => $group->nama,
                            'poin_akumulasi' => (int) ($member->poin_akumulasi ?? 0),
                            'poin_didapat' => $poinBabak,
                            'poin_babak' => $poinBabak,
                            'total_poin' => $totalPoin,
                        ];

                        $rank++;
                    }

                    $groupPayload[] = [
                        'id' => (int) $group->id,
                        'nama' => $group->nama,
                        'standings' => $standings,
                    ];
                }

                $sections[] = [
                    'babak' => $babak,
                    'is_active' => $groups->contains(function ($group) {
                        return (bool) $group->is_aktif;
                    }),
                    'groups' => $groupPayload,
                ];
            }

            return [
                'data' => [
                    'turnamen' => [
                        'id' => (int) $turnamen->id,
                        'nama' => $turnamen->nama,
                        'jenis' => $turnamen->jenis ?? 'mahjong',
                        'status' => $turnamen->status ?? null,
                        'mahjong_is_final' => (bool) ($turnamen->mahjong_is_final ?? false),
                    ],
                    'sections' => $sections,
                    'overall' => self::buildMahjongOverallStandings($connection, $id),
                ],
                'error' => null,
            ];
        } catch (Throwable $e) {
            return [
                'data' => null,
                'error' => 'Database Bornpadel: '.$e->getMessage(),
            ];
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    private static function resolveMahjongGrupBatchForBabak($connection, int $turnamenId, int $babak)
    {
        $active = $connection->table('grup')
            ->where('id_turnamen', $turnamenId)
            ->where('babak', $babak)
            ->where('is_aktif', true)
            ->orderBy('nama')
            ->get();

        if ($active->isNotEmpty()) {
            return $active;
        }

        $latestCreatedAt = $connection->table('grup')
            ->where('id_turnamen', $turnamenId)
            ->where('babak', $babak)
            ->where('is_aktif', false)
            ->max('created_at');

        if (! $latestCreatedAt) {
            return collect();
        }

        return $connection->table('grup')
            ->where('id_turnamen', $turnamenId)
            ->where('babak', $babak)
            ->where('is_aktif', false)
            ->where('created_at', $latestCreatedAt)
            ->orderBy('nama')
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    private static function orderedGroupMembers($connection, int $groupId)
    {
        return $connection->table('grup_member')
            ->where('id_grup', $groupId)
            ->orderByDesc('poin_akumulasi')
            ->orderByDesc('poin_didapat')
            ->orderByDesc('set_menang')
            ->orderByDesc('games_menang')
            ->get();
    }

    /**
     * @param  object  $member
     */
    private static function resolveMahjongBabakPoints($connection, $member, int $babak, int $turnamenId, bool $isActiveGroup): int
    {
        if ($isActiveGroup) {
            return (int) ($member->poin_didapat ?? 0);
        }

        if ((int) ($member->poin_didapat ?? 0) !== 0) {
            return (int) $member->poin_didapat;
        }

        $startAkumulasi = self::getMahjongCarryPointsBeforeBabak(
            $connection,
            (int) ($member->id_turnamen_peserta ?? 0),
            $babak,
            $turnamenId
        );

        return max(0, (int) ($member->poin_akumulasi ?? 0) - $startAkumulasi);
    }

    /**
     * @param  object  $member
     */
    private static function resolveMahjongTotalPoints($member, int $babakPoints, bool $isActiveGroup): int
    {
        if ($isActiveGroup) {
            return (int) ($member->poin_akumulasi ?? 0) + (int) ($member->poin_didapat ?? 0);
        }

        if ((int) ($member->poin_didapat ?? 0) !== 0) {
            return (int) ($member->poin_akumulasi ?? 0) + (int) $member->poin_didapat;
        }

        return (int) ($member->poin_akumulasi ?? 0);
    }

    private static function getMahjongCarryPointsBeforeBabak($connection, int $pesertaId, int $babak, int $turnamenId): int
    {
        if ($pesertaId <= 0 || $babak <= 1) {
            return 0;
        }

        $previousMember = $connection->table('grup_member')
            ->join('grup', 'grup.id', '=', 'grup_member.id_grup')
            ->where('grup_member.id_turnamen_peserta', $pesertaId)
            ->where('grup.id_turnamen', $turnamenId)
            ->where('grup.babak', $babak - 1)
            ->orderByDesc('grup_member.id')
            ->select('grup_member.*')
            ->first();

        if (! $previousMember) {
            return 0;
        }

        if ((int) ($previousMember->poin_didapat ?? 0) !== 0) {
            return (int) $previousMember->poin_akumulasi;
        }

        return (int) ($previousMember->poin_akumulasi ?? 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function buildMahjongOverallStandings($connection, int $turnamenId): array
    {
        $activeMemberIds = $connection->table('grup_member')
            ->join('grup', 'grup.id', '=', 'grup_member.id_grup')
            ->where('grup.id_turnamen', $turnamenId)
            ->where('grup.is_aktif', true)
            ->pluck('grup_member.id');

        if ($activeMemberIds->isNotEmpty()) {
            $members = $connection->table('grup_member')
                ->whereIn('id', $activeMemberIds)
                ->get();
        } else {
            $latestBabak = $connection->table('grup')
                ->where('id_turnamen', $turnamenId)
                ->max('babak');

            if (! $latestBabak) {
                return [];
            }

            $members = $connection->table('grup_member')
                ->join('grup', 'grup.id', '=', 'grup_member.id_grup')
                ->where('grup.id_turnamen', $turnamenId)
                ->where('grup.babak', $latestBabak)
                ->select('grup_member.*', 'grup.nama as grup_nama')
                ->get();
        }

        $rows = $members->map(function ($member) use ($connection) {
            $totalPoin = (int) ($member->poin_akumulasi ?? 0) + (int) ($member->poin_didapat ?? 0);
            $grupNama = $member->grup_nama ?? $connection->table('grup')->where('id', $member->id_grup)->value('nama');

            return [
                'id_pemain' => (int) $member->id_pemain,
                'id_peserta' => (int) $member->id_turnamen_peserta,
                'pemain_ids' => self::resolveStandingPemainIds($connection, $member),
                'nama' => self::resolveMemberDisplayName($connection, $member),
                'grup_nama' => $grupNama,
                'poin_akumulasi' => (int) ($member->poin_akumulasi ?? 0),
                'poin_didapat' => (int) ($member->poin_didapat ?? 0),
                'poin_babak' => (int) ($member->poin_didapat ?? 0),
                'total_poin' => $totalPoin,
            ];
        })->sortByDesc('total_poin')->values();

        $overall = [];
        $rank = 1;

        foreach ($rows as $row) {
            $row['rank'] = $rank;
            $overall[] = $row;
            $rank++;
        }

        return $overall;
    }

    /**
     * @param  object  $member
     * @return array<int, int>
     */
    private static function resolveStandingPemainIds($connection, $member): array
    {
        if (! empty($member->id_turnamen_peserta)) {
            $peserta = $connection->table('turnamen_peserta')
                ->where('id', $member->id_turnamen_peserta)
                ->first();

            if ($peserta) {
                $ids = [];

                if (! empty($peserta->id_pemain1)) {
                    $ids[] = (int) $peserta->id_pemain1;
                }

                if (! empty($peserta->id_pemain2)) {
                    $ids[] = (int) $peserta->id_pemain2;
                }

                if ($ids !== []) {
                    return $ids;
                }
            }
        }

        return ! empty($member->id_pemain) ? [(int) $member->id_pemain] : [];
    }

    /**
     * @param  object  $member
     */
    private static function resolveMemberDisplayName($connection, $member): string
    {
        if (! empty($member->id_turnamen_peserta)) {
            $peserta = $connection->table('turnamen_peserta')
                ->where('id', $member->id_turnamen_peserta)
                ->first();

            if ($peserta) {
                $pemain1 = $peserta->id_pemain1
                    ? $connection->table('m_pemain')->where('id', $peserta->id_pemain1)->value('nama')
                    : null;
                $pemain2 = $peserta->id_pemain2
                    ? $connection->table('m_pemain')->where('id', $peserta->id_pemain2)->value('nama')
                    : null;

                if ($pemain1 && $pemain2) {
                    return trim($pemain1.' / '.$pemain2);
                }

                if ($pemain1) {
                    return (string) $pemain1;
                }

                if ($pemain2) {
                    return (string) $pemain2;
                }
            }
        }

        if (! empty($member->id_pemain)) {
            $nama = $connection->table('m_pemain')->where('id', $member->id_pemain)->value('nama');

            if ($nama) {
                return (string) $nama;
            }
        }

        return '-';
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findMahjongTournament(int $id): ?array
    {
        $result = self::fetch();

        if ($result['error'] !== null) {
            return null;
        }

        foreach ($result['items'] as $item) {
            if ((int) ($item['id'] ?? 0) === $id) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{data: array<string, mixed>|null, message: string|null, error: string|null}
     */
    public static function registerPlayer(array $payload): array
    {
        $fromDatabase = self::registerPlayerFromDatabase($payload);

        if ($fromDatabase['error'] === null) {
            return self::publicRegisterResult($fromDatabase);
        }

        if (! empty($fromDatabase['retry_via_api'])) {
            return self::registerPlayerFromApi($payload);
        }

        return self::publicRegisterResult($fromDatabase);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{data: array<string, mixed>|null, message: string|null, error: string|null, retry_via_api?: bool}
     */
    private static function registerPlayerFromDatabase(array $payload): array
    {
        $fail = static function (string $message, bool $retryViaApi = false): array {
            return [
                'data' => null,
                'message' => null,
                'error' => $message,
                'retry_via_api' => $retryViaApi,
            ];
        };

        try {
            $connection = DB::connection('bornpadel');

            if (! Schema::connection('bornpadel')->hasTable('m_turnamen')
                || ! Schema::connection('bornpadel')->hasTable('m_pemain')
                || ! Schema::connection('bornpadel')->hasTable('turnamen_peserta')) {
                return $fail('Database Bornpadel belum memiliki tabel pendaftaran.', true);
            }

            $turnamenId = (int) ($payload['id_turnamen'] ?? 0);
            $turnamen = $connection->table('m_turnamen')->where('id', $turnamenId)->first();

            if (! $turnamen) {
                return $fail('Turnamen tidak ditemukan.', true);
            }

            if (($turnamen->jenis ?? '') !== 'mahjong') {
                return $fail('Turnamen bukan turnamen mahjong.');
            }

            if (($turnamen->status ?? '') !== 'open') {
                return $fail('Pendaftaran turnamen tidak dibuka.');
            }

            $nama = trim((string) ($payload['nama'] ?? ''));
            if ($nama === '') {
                return $fail('Nama wajib diisi.');
            }

            $noHp = trim((string) ($payload['no_hp'] ?? ''));
            if ($noHp === '') {
                return $fail('Nomor HP wajib diisi.');
            }

            if (strlen($noHp) > 20) {
                return $fail('Nomor HP terlalu panjang (maks. 20 karakter).');
            }

            $gender = (string) ($payload['gender'] ?? '');
            if (! in_array($gender, ['male', 'female'], true)) {
                return $fail('Jenis kelamin tidak valid.');
            }

            $rating = (float) ($payload['rating'] ?? 0);
            $tglLahir = isset($payload['tgl_lahir']) && $payload['tgl_lahir'] !== ''
                ? (string) $payload['tgl_lahir']
                : null;
            $usia = null;

            if ($tglLahir !== null) {
                try {
                    $birthDate = Carbon::parse($tglLahir);
                    $tglLahir = $birthDate->toDateString();
                    $usia = $birthDate->age;
                } catch (Throwable $e) {
                    return $fail('Tanggal lahir tidak valid.');
                }
            }

            $existingPemain = $connection->table('m_pemain')->where('no_hp', $noHp)->first();

            if ($existingPemain !== null) {
                $alreadyRegistered = $connection->table('turnamen_peserta')
                    ->where('id_turnamen', $turnamenId)
                    ->where(function ($query) use ($existingPemain) {
                        $query->where('id_pemain1', $existingPemain->id)
                            ->orWhere('id_pemain2', $existingPemain->id);
                    })
                    ->exists();

                if ($alreadyRegistered) {
                    return $fail('Nomor HP sudah terdaftar di turnamen ini.');
                }
            }

            $now = now();

            $result = $connection->transaction(function () use (
                $connection,
                $existingPemain,
                $nama,
                $noHp,
                $gender,
                $rating,
                $tglLahir,
                $usia,
                $turnamenId,
                $now
            ) {
                $pemainData = [
                    'nama' => $nama,
                    'gender' => $gender,
                    'no_hp' => $noHp,
                    'rating' => $rating,
                    'tgl_lahir' => $tglLahir,
                    'usia' => $usia,
                    'updated_at' => $now,
                ];

                if ($existingPemain !== null) {
                    $connection->table('m_pemain')
                        ->where('id', $existingPemain->id)
                        ->update($pemainData);

                    $pemainId = (int) $existingPemain->id;
                } else {
                    $pemainId = (int) $connection->table('m_pemain')->insertGetId(array_merge($pemainData, [
                        'created_at' => $now,
                    ]));
                }

                $pesertaId = (int) $connection->table('turnamen_peserta')->insertGetId([
                    'id_turnamen' => $turnamenId,
                    'id_pemain1' => $pemainId,
                    'id_pemain2' => null,
                    'status' => 'unpaid',
                    'bukti_bayar' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                return [
                    'pemain_id' => $pemainId,
                    'peserta_id' => $pesertaId,
                ];
            });

            return [
                'data' => [
                    'turnamen_id' => $turnamenId,
                    'pemain_id' => $result['pemain_id'],
                    'peserta_id' => $result['peserta_id'],
                ],
                'message' => 'Pemain berhasil didaftarkan.',
                'error' => null,
            ];
        } catch (Throwable $e) {
            return $fail('Database Bornpadel: '.$e->getMessage(), true);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{data: array<string, mixed>|null, message: string|null, error: string|null}
     */
    private static function registerPlayerFromApi(array $payload): array
    {
        $apiUrl = rtrim((string) config('services.bornpadel.api_url'), '/');
        $token = config('services.bornpadel.api_token');

        if (! $token || $apiUrl === '') {
            return [
                'data' => null,
                'message' => null,
                'error' => 'Token atau URL API Bornpadel belum dikonfigurasi.',
            ];
        }

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->withToken($token)
                ->post($apiUrl.'/register-player', $payload);

            if ($response->successful() && $response->json('success') === true) {
                return [
                    'data' => $response->json('data'),
                    'message' => $response->json('message') ?? 'Pemain berhasil didaftarkan.',
                    'error' => null,
                ];
            }

            return [
                'data' => null,
                'message' => null,
                'error' => self::formatApiErrorMessage($response),
            ];
        } catch (Throwable $e) {
            return [
                'data' => null,
                'message' => null,
                'error' => 'Tidak dapat terhubung ke server.',
            ];
        }
    }

    /**
     * @param  array{data: array<string, mixed>|null, message: string|null, error: string|null, retry_via_api?: bool}  $result
     * @return array{data: array<string, mixed>|null, message: string|null, error: string|null}
     */
    private static function publicRegisterResult(array $result): array
    {
        unset($result['retry_via_api']);

        return $result;
    }

    private static function formatApiErrorMessage($response): string
    {
        $errors = $response->json('errors');

        if (is_array($errors) && $errors !== []) {
            $messages = [];

            foreach ($errors as $field => $fieldErrors) {
                if (is_array($fieldErrors)) {
                    foreach ($fieldErrors as $message) {
                        $messages[] = $message;
                    }
                }
            }

            if ($messages !== []) {
                return implode(' ', $messages);
            }
        }

        return $response->json('message') ?? 'Gagal mendaftarkan pemain.';
    }

    /**
     * @return array{data: array<string, mixed>|null, error: string|null}
     */
    private static function fetchGroupStandingsFromApi(int $id): array
    {
        $apiUrl = rtrim((string) config('services.bornpadel.api_url'), '/');
        $token = config('services.bornpadel.api_token');

        if (! $token || $apiUrl === '') {
            return [
                'data' => null,
                'error' => 'Token atau URL API Bornpadel belum dikonfigurasi.',
            ];
        }

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->withToken($token)
                ->get($apiUrl.'/tournaments/'.$id.'/group-standings');

            if ($response->successful() && $response->json('success') === true) {
                return [
                    'data' => $response->json('data'),
                    'error' => null,
                ];
            }

            return [
                'data' => null,
                'error' => $response->json('message') ?? 'Gagal memuat klasemen turnamen.',
            ];
        } catch (Throwable $e) {
            return [
                'data' => null,
                'error' => 'Tidak dapat terhubung ke server turnamen.',
            ];
        }
    }
}
