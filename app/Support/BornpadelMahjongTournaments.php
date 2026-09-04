<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
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
                return self::mapTournamentRow($row);
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
     * Public participant list for a mahjong tournament (names + status only).
     *
     * @return array{type: string, items: array<int, array<string, mixed>>, error: string|null}
     */
    public static function fetchParticipants(int $id, $idKategori = null): array
    {
        $fromDatabase = self::fetchParticipantsFromDatabase($id, $idKategori);
        if ($fromDatabase['error'] === null) {
            return $fromDatabase;
        }

        $fromApi = self::fetchParticipantsFromApi($id, $idKategori);
        if ($fromApi['error'] === null) {
            return $fromApi;
        }

        return [
            'type' => 'single',
            'items' => [],
            'error' => $fromDatabase['error'] ?? $fromApi['error'],
        ];
    }

    /**
     * @return array{type: string, items: array<int, array<string, mixed>>, error: string|null}
     */
    private static function fetchParticipantsFromDatabase(int $id, $idKategori = null): array
    {
        try {
            $connection = DB::connection('bornpadel');

            if (! Schema::connection('bornpadel')->hasTable('m_turnamen')
                || ! Schema::connection('bornpadel')->hasTable('m_pemain')
                || ! Schema::connection('bornpadel')->hasTable('turnamen_peserta')) {
                return [
                    'type' => 'single',
                    'items' => [],
                    'error' => 'Database Bornpadel belum memiliki tabel peserta.',
                ];
            }

            $turnamen = $connection->table('m_turnamen')
                ->where('id', $id)
                ->where('jenis', 'mahjong')
                ->first();

            if (! $turnamen) {
                return [
                    'type' => 'single',
                    'items' => [],
                    'error' => 'Turnamen tidak ditemukan.',
                ];
            }

            $kategoriId = $idKategori !== null && $idKategori !== ''
                ? (int) $idKategori
                : null;

            if ($kategoriId === null) {
                $resolved = self::resolveKategoriForTournament($connection, $id, null, false);
                if ($resolved['ok'] && ! empty($resolved['kategori'])) {
                    $kategoriId = (int) $resolved['kategori']->id;
                }
            }

            $query = $connection->table('turnamen_peserta')
                ->leftJoin('m_pemain', 'm_pemain.id', '=', 'turnamen_peserta.id_pemain1')
                ->where('turnamen_peserta.id_turnamen', $id)
                ->where('turnamen_peserta.status', '!=', 'rejected')
                ->orderBy('turnamen_peserta.id')
                ->select([
                    'turnamen_peserta.id',
                    'turnamen_peserta.status',
                    'm_pemain.nama',
                ]);

            if ($kategoriId !== null
                && Schema::connection('bornpadel')->hasColumn('turnamen_peserta', 'id_kategori')) {
                $query->where('turnamen_peserta.id_kategori', $kategoriId);
            }

            $items = $query->get()->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'nama' => $row->nama ?: '—',
                    'status' => $row->status ?? null,
                ];
            })->values()->all();

            return [
                'type' => 'single',
                'items' => $items,
                'error' => null,
            ];
        } catch (Throwable $e) {
            return [
                'type' => 'single',
                'items' => [],
                'error' => 'Database Bornpadel: '.$e->getMessage(),
            ];
        }
    }

    /**
     * @return array{type: string, items: array<int, array<string, mixed>>, error: string|null}
     */
    private static function fetchParticipantsFromApi(int $id, $idKategori = null): array
    {
        $apiUrl = rtrim((string) config('services.bornpadel.api_url'), '/');
        $token = config('services.bornpadel.api_token');

        if (! $token || $apiUrl === '') {
            return [
                'type' => 'single',
                'items' => [],
                'error' => 'Token atau URL API Bornpadel belum dikonfigurasi.',
            ];
        }

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->withToken($token)
                ->get($apiUrl.'/tournaments/'.$id.'/participants', array_filter([
                    'id_kategori' => $idKategori,
                ], static function ($value) {
                    return $value !== null && $value !== '';
                }));

            if ($response->successful() && $response->json('success') === true) {
                $data = $response->json('data') ?? [];
                $items = is_array($data['items'] ?? null) ? $data['items'] : [];

                return [
                    'type' => (string) ($data['type'] ?? 'single'),
                    'items' => array_map(static function ($item) {
                        $item = is_array($item) ? $item : [];

                        return [
                            'id' => isset($item['id']) ? (int) $item['id'] : null,
                            'nama' => $item['nama'] ?? $item['display'] ?? $item['label'] ?? '—',
                            'status' => $item['status'] ?? null,
                        ];
                    }, $items),
                    'error' => null,
                ];
            }

            return [
                'type' => 'single',
                'items' => [],
                'error' => $response->json('message') ?? 'Gagal memuat daftar pemain dari API.',
            ];
        } catch (Throwable $e) {
            return [
                'type' => 'single',
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
                $table = self::buildMahjongBabakTableFromDb($connection, $id, $babak);

                $sections[] = [
                    'babak' => $babak,
                    'is_active' => $groups->contains(function ($group) {
                        return (bool) $group->is_aktif;
                    }),
                    'rounds' => $table['rounds'],
                    'rows' => $table['rows'],
                    'groups' => [],
                    'recap' => $table['rows'],
                ];
            }

            $recapSections = array_map(static function (array $section) {
                return [
                    'babak' => $section['babak'],
                    'is_active' => $section['is_active'],
                    'rounds' => $section['rounds'],
                    'standings' => $section['recap'],
                ];
            }, $sections);

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
                    'recap' => $recapSections,
                    'babak_numbers' => $babakNumbers->map(static function ($babak) {
                        return (int) $babak;
                    })->values()->all(),
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
     * @return array{rounds: array<int, array<string, mixed>>, rows: array<int, array<string, mixed>>}
     */
    private static function buildMahjongBabakTableFromDb($connection, int $turnamenId, int $babak): array
    {
        $roundBatches = self::getMahjongRoundBatchesForBabak($connection, $turnamenId, $babak);

        if ($roundBatches === []) {
            return ['rounds' => [], 'rows' => []];
        }

        $rounds = [];
        foreach ($roundBatches as $index => $batch) {
            $rounds[] = [
                'round' => $index + 1,
                'label' => 'Ronde '.($index + 1),
            ];
        }

        $pesertaIds = [];
        foreach ($roundBatches as $batch) {
            foreach ($batch as $grup) {
                foreach ($grup->members as $member) {
                    if (! empty($member->id_turnamen_peserta)) {
                        $pesertaIds[(int) $member->id_turnamen_peserta] = true;
                    }
                }
            }
        }
        $pesertaIds = array_keys($pesertaIds);

        $rows = [];

        foreach ($pesertaIds as $pesertaId) {
            $roundScores = [];
            $latestMember = null;

            foreach ($roundBatches as $roundIndex => $batch) {
                $member = self::findMahjongMemberInBatch($batch, $pesertaId);

                if ($member) {
                    $latestMember = $member;
                    $roundScores[] = self::resolveMahjongRoundPoints(
                        $connection,
                        $member,
                        $roundBatches,
                        (int) $roundIndex,
                        $babak,
                        $turnamenId
                    );
                } else {
                    $roundScores[] = 0;
                }
            }

            if ($latestMember === null) {
                continue;
            }

            $totalBabak = array_sum($roundScores);

            $rows[] = [
                'id_pemain' => (int) ($latestMember->id_pemain ?? 0),
                'id_peserta' => (int) ($latestMember->id_turnamen_peserta ?? 0),
                'pemain_ids' => self::resolveStandingPemainIds($connection, $latestMember),
                'nama' => self::resolveMemberDisplayName($connection, $latestMember),
                'round_scores' => $roundScores,
                'menang' => self::countMahjongWinsForPeserta($connection, $turnamenId, $babak, $pesertaId),
                'total_babak' => $totalBabak,
                'poin_babak' => $totalBabak,
                'total_poin' => self::resolveMahjongTotalPoints(
                    $latestMember,
                    $totalBabak,
                    (bool) ($latestMember->_grup_is_aktif ?? false)
                ),
            ];
        }

        usort($rows, static function (array $a, array $b) {
            return ($b['total_babak'] ?? 0) <=> ($a['total_babak'] ?? 0);
        });

        foreach ($rows as $index => &$row) {
            $row['rank'] = $index + 1;
        }
        unset($row);

        return ['rounds' => $rounds, 'rows' => $rows];
    }

    /**
     * Group grups of a babak into round batches (by the `ronde` column), each
     * batch carrying its members with the parent grup's active flag attached.
     *
     * @return array<int, array<int, object>>
     */
    private static function getMahjongRoundBatchesForBabak($connection, int $turnamenId, int $babak): array
    {
        $grups = $connection->table('grup')
            ->where('id_turnamen', $turnamenId)
            ->where('babak', $babak)
            ->orderBy('ronde')
            ->orderBy('id')
            ->get();

        $byRonde = [];

        foreach ($grups as $grup) {
            $ronde = (int) ($grup->ronde ?? 0);
            if ($ronde <= 0) {
                $ronde = 1;
            }

            $members = self::orderedGroupMembers($connection, $grup->id)->all();
            foreach ($members as $member) {
                $member->_grup_is_aktif = (bool) $grup->is_aktif;
            }

            $grup->members = $members;
            $byRonde[$ronde][] = $grup;
        }

        ksort($byRonde);

        return array_values($byRonde);
    }

    private static function countMahjongWinsForPeserta($connection, int $turnamenId, int $babak, int $pesertaId): int
    {
        try {
            if (! Schema::connection('bornpadel')->hasTable('mahjong_poin_entry')
                || ! Schema::connection('bornpadel')->hasColumn('mahjong_poin_entry', 'is_winner')) {
                return 0;
            }

            return (int) $connection->table('mahjong_poin_entry')
                ->join('grup_member', 'grup_member.id', '=', 'mahjong_poin_entry.id_grup_member')
                ->join('grup', 'grup.id', '=', 'grup_member.id_grup')
                ->where('grup.id_turnamen', $turnamenId)
                ->where('grup.babak', $babak)
                ->where('grup_member.id_turnamen_peserta', $pesertaId)
                ->where('mahjong_poin_entry.is_winner', 1)
                ->count();
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * @param  array<int, object>  $batch
     * @return object|null
     */
    private static function findMahjongMemberInBatch(array $batch, int $pesertaId)
    {
        foreach ($batch as $grup) {
            foreach ($grup->members as $member) {
                if ((int) ($member->id_turnamen_peserta ?? 0) === $pesertaId) {
                    return $member;
                }
            }
        }

        return null;
    }

    /**
     * @param  object  $member
     * @param  array<int, array<int, object>>  $roundBatches
     */
    private static function resolveMahjongRoundPoints($connection, $member, array $roundBatches, int $roundIndex, int $babak, int $turnamenId): int
    {
        if (! empty($member->_grup_is_aktif)) {
            return (int) ($member->poin_didapat ?? 0);
        }

        if ((int) ($member->poin_didapat ?? 0) !== 0) {
            return (int) $member->poin_didapat;
        }

        $startTotal = self::resolveMahjongRoundStartTotal(
            $connection,
            (int) ($member->id_turnamen_peserta ?? 0),
            $roundBatches,
            $roundIndex,
            $babak,
            $turnamenId
        );

        return (int) ($member->poin_akumulasi ?? 0) - $startTotal;
    }

    /**
     * @param  array<int, array<int, object>>  $roundBatches
     */
    private static function resolveMahjongRoundStartTotal($connection, int $pesertaId, array $roundBatches, int $roundIndex, int $babak, int $turnamenId): int
    {
        if ($roundIndex > 0 && $pesertaId > 0) {
            $prevBatch = $roundBatches[$roundIndex - 1] ?? null;

            if ($prevBatch) {
                $prevMember = self::findMahjongMemberInBatch($prevBatch, $pesertaId);

                if ($prevMember) {
                    return (int) ($prevMember->poin_akumulasi ?? 0);
                }
            }
        }

        return self::getMahjongCarryPointsBeforeBabak($connection, $pesertaId, $babak, $turnamenId);
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
     * @param  array<int, array<string, mixed>>  $groups
     * @return array<int, array<string, mixed>>
     */
    private static function buildMahjongBabakRecap(array $groups): array
    {
        $rows = [];

        foreach ($groups as $group) {
            foreach ($group['standings'] ?? [] as $row) {
                $rows[] = $row;
            }
        }

        usort($rows, static function (array $a, array $b) {
            $poinA = (int) ($a['poin_babak'] ?? $a['poin_didapat'] ?? 0);
            $poinB = (int) ($b['poin_babak'] ?? $b['poin_didapat'] ?? 0);

            return $poinB <=> $poinA;
        });

        $recap = [];

        foreach ($rows as $index => $row) {
            $recap[] = [
                'rank' => $index + 1,
                'id_pemain' => (int) ($row['id_pemain'] ?? 0),
                'id_peserta' => (int) ($row['id_peserta'] ?? 0),
                'pemain_ids' => $row['pemain_ids'] ?? [],
                'nama' => $row['nama'] ?? '—',
                'grup_nama' => $row['grup_nama'] ?? null,
                'poin_babak' => (int) ($row['poin_babak'] ?? $row['poin_didapat'] ?? 0),
                'total_poin' => (int) ($row['total_poin'] ?? 0),
            ];
        }

        return $recap;
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
                $pemain1Id = isset($peserta->id_pemain1) ? $peserta->id_pemain1 : null;
                $pemain2Id = isset($peserta->id_pemain2) ? $peserta->id_pemain2 : null;

                if (! empty($pemain1Id)) {
                    $ids[] = (int) $pemain1Id;
                }

                if (! empty($pemain2Id)) {
                    $ids[] = (int) $pemain2Id;
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
                $pemain1Id = isset($peserta->id_pemain1) ? $peserta->id_pemain1 : null;
                $pemain2Id = isset($peserta->id_pemain2) ? $peserta->id_pemain2 : null;
                $pemain1 = $pemain1Id
                    ? $connection->table('m_pemain')->where('id', $pemain1Id)->value('nama')
                    : null;
                $pemain2 = $pemain2Id
                    ? $connection->table('m_pemain')->where('id', $pemain2Id)->value('nama')
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
        $fromDatabase = self::findMahjongTournamentFromDatabase($id);
        if ($fromDatabase !== null) {
            return $fromDatabase;
        }

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
     * @return array<string, mixed>|null
     */
    private static function findMahjongTournamentFromDatabase(int $id): ?array
    {
        try {
            $connection = DB::connection('bornpadel');

            if (! Schema::connection('bornpadel')->hasTable('m_turnamen')
                || ! Schema::connection('bornpadel')->hasColumn('m_turnamen', 'jenis')) {
                return null;
            }

            $row = $connection->table('m_turnamen')
                ->where('id', $id)
                ->where('jenis', 'mahjong')
                ->first();

            if (! $row) {
                return null;
            }

            return self::mapTournamentRow($row);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * @param  object  $row
     * @return array<string, mixed>
     */
    private static function mapTournamentRow($row): array
    {
        $foto = property_exists($row, 'foto') ? ($row->foto ?? null) : null;
        $kategoriList = self::listKategoriForTournament((int) $row->id);
        $defaultKategori = self::pickDefaultKategori($kategoriList);

        return [
            'id' => (int) $row->id,
            'nama' => $row->nama,
            'tanggal' => $row->tanggal ?? null,
            'harga' => $defaultKategori['harga'] ?? ($row->harga ?? 0),
            'syarat' => $row->syarat ?? null,
            'jenis' => $row->jenis ?? 'mahjong',
            'jenis_label' => 'Mahjong',
            'status' => $row->status ?? null,
            'mahjong_is_final' => (bool) (
                $defaultKategori['mahjong_is_final']
                ?? ($row->mahjong_is_final ?? false)
            ),
            // Same as Bornpadel guest landing: turnamen status drives the Daftar button.
            'registration_open' => ($row->status ?? null) === 'open',
            'foto' => $foto,
            'share_image_url' => self::tournamentShareImageUrl($foto),
            'kategori' => $kategoriList,
            'default_kategori_id' => $defaultKategori['id'] ?? null,
            'has_multiple_kategori' => count($kategoriList) > 1,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function listKategoriForTournament(int $turnamenId): array
    {
        try {
            if (! Schema::connection('bornpadel')->hasTable('turnamen_kategori')) {
                return [];
            }

            return DB::connection('bornpadel')
                ->table('turnamen_kategori')
                ->where('id_turnamen', $turnamenId)
                ->orderBy('urutan')
                ->orderBy('id')
                ->get()
                ->map(function ($row) {
                    return [
                        'id' => (int) $row->id,
                        'nama' => $row->nama,
                        'is_default' => (bool) ($row->is_default ?? false),
                        'urutan' => (int) ($row->urutan ?? 0),
                        'harga' => $row->harga ?? 0,
                        'status' => $row->status ?? null,
                        'mahjong_is_final' => (bool) ($row->mahjong_is_final ?? false),
                        'registration_open' => ($row->status ?? null) === 'open',
                    ];
                })
                ->values()
                ->all();
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $kategoriList
     * @return array<string, mixed>|null
     */
    private static function pickDefaultKategori(array $kategoriList): ?array
    {
        if ($kategoriList === []) {
            return null;
        }

        foreach ($kategoriList as $kat) {
            if (! empty($kat['is_default'])) {
                return $kat;
            }
        }

        return $kategoriList[0];
    }

    /**
     * @return array{ok: bool, kategori: object|null, error: string|null, retry_via_api?: bool}
     */
    private static function resolveKategoriForTournament($connection, int $turnamenId, $idKategori = null, bool $requireOpen = false): array
    {
        if (! Schema::connection('bornpadel')->hasTable('turnamen_kategori')) {
            return [
                'ok' => true,
                'kategori' => null,
                'error' => null,
            ];
        }

        $idProvided = $idKategori !== null && $idKategori !== '';

        if ($idProvided) {
            $kategori = $connection->table('turnamen_kategori')
                ->where('id_turnamen', $turnamenId)
                ->where('id', (int) $idKategori)
                ->first();

            if (! $kategori) {
                return [
                    'ok' => false,
                    'kategori' => null,
                    'error' => 'Kategori tidak ditemukan untuk turnamen ini.',
                ];
            }
        } else {
            $count = $connection->table('turnamen_kategori')
                ->where('id_turnamen', $turnamenId)
                ->count();

            if ($count > 1) {
                return [
                    'ok' => false,
                    'kategori' => null,
                    'error' => 'Parameter id_kategori wajib diisi karena turnamen memiliki lebih dari satu kategori.',
                ];
            }

            $kategori = $connection->table('turnamen_kategori')
                ->where('id_turnamen', $turnamenId)
                ->where('is_default', true)
                ->first();

            if (! $kategori) {
                $kategori = $connection->table('turnamen_kategori')
                    ->where('id_turnamen', $turnamenId)
                    ->orderBy('urutan')
                    ->orderBy('id')
                    ->first();
            }

            if (! $kategori) {
                $turnamen = $connection->table('m_turnamen')->where('id', $turnamenId)->first();
                if (! $turnamen) {
                    return [
                        'ok' => false,
                        'kategori' => null,
                        'error' => 'Turnamen tidak ditemukan.',
                        'retry_via_api' => true,
                    ];
                }

                $now = now();
                $kategoriId = (int) $connection->table('turnamen_kategori')->insertGetId([
                    'id_turnamen' => $turnamenId,
                    'nama' => 'Umum',
                    'is_default' => 1,
                    'urutan' => 1,
                    'harga' => $turnamen->harga ?? 0,
                    'maks_peserta' => $turnamen->maks_peserta ?? null,
                    'status' => in_array($turnamen->status ?? '', ['draft', 'open', 'ongoing', 'completed'], true)
                        ? $turnamen->status
                        : 'draft',
                    'mahjong_is_final' => (bool) ($turnamen->mahjong_is_final ?? false),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $kategori = $connection->table('turnamen_kategori')->where('id', $kategoriId)->first();
            }
        }

        if ($requireOpen) {
            $turnamen = $connection->table('m_turnamen')->where('id', $turnamenId)->first();
            if (! $turnamen || ($turnamen->status ?? null) !== 'open') {
                return [
                    'ok' => false,
                    'kategori' => $kategori,
                    'error' => 'Pendaftaran turnamen tidak dibuka.',
                ];
            }
        }

        return [
            'ok' => true,
            'kategori' => $kategori,
            'error' => null,
        ];
    }

    private static function pesertaQueryForPemain($connection, int $turnamenId, int $pemainId, $idKategori = null)
    {
        $query = $connection->table('turnamen_peserta')
            ->where('id_turnamen', $turnamenId)
            ->where('id_pemain1', $pemainId);

        if ($idKategori !== null && Schema::connection('bornpadel')->hasColumn('turnamen_peserta', 'id_kategori')) {
            $query->where('id_kategori', (int) $idKategori);
        }

        return $query;
    }

    public static function defaultShareImageUrl(): string
    {
        return self::absoluteUrl(asset('public/assets/img/logo.png'));
    }

    public static function tournamentShareImageUrl(?string $foto = null): string
    {
        if ($foto) {
            $normalized = str_replace('\\', '/', ltrim($foto, '/'));
            $baseUrl = rtrim((string) config('services.bornpadel.public_url'), '/');
            if ($baseUrl !== '') {
                return self::absoluteUrl($baseUrl.'/public/'.$normalized);
            }

            $fromBornpadel = self::bornpadelPublicUrl($foto);
            if ($fromBornpadel) {
                return self::absoluteUrl($fromBornpadel);
            }
        }

        return self::defaultShareImageUrl();
    }

    private static function absoluteUrl(string $url): string
    {
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            return $url;
        }

        return url($url);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{data: array<string, mixed>|null, message: string|null, error: string|null}
     */
    public static function registerPlayer(array $payload, ?UploadedFile $foto = null): array
    {
        $fromDatabase = self::registerPlayerFromDatabase($payload, $foto);

        if ($fromDatabase['error'] === null) {
            return self::publicRegisterResult($fromDatabase);
        }

        if (! empty($fromDatabase['retry_via_api'])) {
            return self::registerPlayerFromApi($payload, $foto);
        }

        return self::publicRegisterResult($fromDatabase);
    }

    /**
     * @return array{data: array<string, mixed>|null, error: string|null}
     */
    public static function checkRegistration(int $turnamenId, string $noHp, $idKategori = null): array
    {
        $fromDatabase = self::checkRegistrationFromDatabase($turnamenId, $noHp, $idKategori);

        if ($fromDatabase['error'] === null) {
            return $fromDatabase;
        }

        if (! empty($fromDatabase['retry_via_api'])) {
            return self::checkRegistrationFromApi($turnamenId, $noHp, $idKategori);
        }

        return $fromDatabase;
    }

    /**
     * @return array{data: array<string, mixed>|null, error: string|null, retry_via_api?: bool}
     */
    private static function checkRegistrationFromDatabase(int $turnamenId, string $noHp, $idKategori = null): array
    {
        $fail = static function (string $message, bool $retryViaApi = false): array {
            return [
                'data' => null,
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

            $turnamen = $connection->table('m_turnamen')->where('id', $turnamenId)->first();

            if (! $turnamen) {
                return $fail('Turnamen tidak ditemukan.', true);
            }

            $resolved = self::resolveKategoriForTournament($connection, $turnamenId, $idKategori, false);
            if (! $resolved['ok']) {
                return $fail($resolved['error'] ?? 'Kategori tidak valid.', ! empty($resolved['retry_via_api']));
            }
            $kategori = $resolved['kategori'];
            $kategoriId = $kategori ? (int) $kategori->id : null;

            $pemain = $connection->table('m_pemain')->where('no_hp', $noHp)->first();

            if (! $pemain) {
                return [
                    'data' => [
                        'registered' => false,
                        'turnamen_id' => $turnamenId,
                        'kategori_id' => $kategoriId,
                        'no_hp' => $noHp,
                        'pemain_exists' => false,
                        'pemain' => null,
                        'registration' => null,
                    ],
                    'error' => null,
                ];
            }

            $peserta = self::pesertaQueryForPemain($connection, $turnamenId, (int) $pemain->id, $kategoriId)->first();

            return [
                'data' => [
                    'registered' => $peserta !== null,
                    'turnamen_id' => $turnamenId,
                    'kategori_id' => $kategoriId,
                    'no_hp' => $pemain->no_hp,
                    'pemain_exists' => true,
                    'pemain' => [
                        'id' => (int) $pemain->id,
                        'nama' => $pemain->nama,
                        'gender' => $pemain->gender,
                        'foto' => $pemain->foto ?? null,
                        'foto_url' => self::pemainPhotoUrl($pemain->foto ?? null),
                    ],
                    'registration' => $peserta ? [
                        'peserta_id' => (int) $peserta->id,
                        'status' => $peserta->status,
                        'sumber' => $peserta->sumber ?? null,
                        'bukti_bayar' => $peserta->bukti_bayar ?? null,
                        'bukti_bayar_url' => self::paymentReceiptUrl($peserta->bukti_bayar ?? null),
                        'paired_at' => $peserta->paired_at ?? null,
                    ] : null,
                ],
                'error' => null,
            ];
        } catch (Throwable $e) {
            return $fail('Database Bornpadel: '.$e->getMessage(), true);
        }
    }

    /**
     * @return array{data: array<string, mixed>|null, error: string|null}
     */
    private static function checkRegistrationFromApi(int $turnamenId, string $noHp, $idKategori = null): array
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
            $params = array_filter([
                'id_turnamen' => $turnamenId,
                'no_hp' => $noHp,
                'id_kategori' => $idKategori,
            ], static function ($value) {
                return $value !== null && $value !== '';
            });

            $response = Http::timeout(15)
                ->acceptJson()
                ->withToken($token)
                ->get($apiUrl.'/registration-check', $params);

            if ($response->successful() && $response->json('success') === true) {
                return [
                    'data' => $response->json('data'),
                    'error' => null,
                ];
            }

            return [
                'data' => null,
                'error' => $response->json('message') ?? 'Gagal memeriksa status pendaftaran.',
            ];
        } catch (Throwable $e) {
            return [
                'data' => null,
                'error' => 'Tidak dapat terhubung ke server turnamen.',
            ];
        }
    }

    public static function registrationStatusLabel(?string $status): string
    {
        switch ($status) {
            case 'unpaid':
                return 'Belum bayar';
            case 'pending':
                return 'Menunggu verifikasi';
            case 'paid':
                return 'Sudah bayar';
            case 'approved':
                return 'Disetujui';
            case 'rejected':
                return 'Ditolak';
            default:
                return $status ? ucfirst(str_replace('_', ' ', $status)) : '—';
        }
    }

    public static function genderLabel(?string $gender): string
    {
        switch ($gender) {
            case 'male':
                return 'Laki-laki';
            case 'female':
                return 'Perempuan';
            default:
                return $gender ?: '—';
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{data: array<string, mixed>|null, message: string|null, error: string|null}
     */
    public static function uploadPaymentReceipt(array $payload, UploadedFile $file): array
    {
        $fromDatabase = self::uploadPaymentReceiptFromDatabase($payload, $file);

        if ($fromDatabase['error'] === null) {
            return $fromDatabase;
        }

        if (! empty($fromDatabase['retry_via_api'])) {
            return self::uploadPaymentReceiptFromApi($payload, $file);
        }

        return $fromDatabase;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{data: array<string, mixed>|null, message: string|null, error: string|null, retry_via_api?: bool}
     */
    private static function uploadPaymentReceiptFromDatabase(array $payload, UploadedFile $file): array
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

            if (! Schema::connection('bornpadel')->hasTable('turnamen_peserta')) {
                return $fail('Database Bornpadel belum memiliki tabel pendaftaran.', true);
            }

            $peserta = self::resolvePesertaFromDatabase($connection, $payload);

            if (! $peserta) {
                return $fail('Pendaftaran turnamen tidak ditemukan.', true);
            }

            $storedPath = self::storePaymentReceiptFile($file);
            $updates = [
                'bukti_bayar' => $storedPath,
                'updated_at' => now(),
            ];

            if (in_array($peserta->status, ['unpaid', 'pending'], true)) {
                $updates['status'] = 'paid';
            }

            $connection->table('turnamen_peserta')
                ->where('id', $peserta->id)
                ->update($updates);

            $status = $updates['status'] ?? $peserta->status;

            return [
                'data' => [
                    'peserta_id' => (int) $peserta->id,
                    'turnamen_id' => (int) $peserta->id_turnamen,
                    'status' => $status,
                    'bukti_bayar' => $storedPath,
                    'bukti_bayar_url' => self::paymentReceiptUrl($storedPath),
                ],
                'message' => 'Bukti bayar berhasil diunggah.',
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
    private static function uploadPaymentReceiptFromApi(array $payload, UploadedFile $file): array
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
            $requestPayload = array_filter([
                'id_turnamen' => $payload['id_turnamen'] ?? null,
                'id_kategori' => $payload['id_kategori'] ?? null,
                'no_hp' => $payload['no_hp'] ?? null,
                'peserta_id' => $payload['peserta_id'] ?? null,
            ], static function ($value) {
                return $value !== null && $value !== '';
            });

            $response = Http::timeout(30)
                ->acceptJson()
                ->withToken($token)
                ->attach(
                    'bukti_bayar',
                    file_get_contents($file->getRealPath()),
                    $file->getClientOriginalName()
                )
                ->post($apiUrl.'/payment-receipt', $requestPayload);

            if ($response->successful() && $response->json('success') === true) {
                return [
                    'data' => $response->json('data'),
                    'message' => $response->json('message') ?? 'Bukti bayar berhasil diunggah.',
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
                'error' => 'Tidak dapat terhubung ke server turnamen.',
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return object|null
     */
    private static function resolvePesertaFromDatabase($connection, array $payload)
    {
        if (! empty($payload['peserta_id'])) {
            return $connection->table('turnamen_peserta')
                ->where('id', (int) $payload['peserta_id'])
                ->first();
        }

        $turnamenId = (int) ($payload['id_turnamen'] ?? 0);
        $noHp = trim((string) ($payload['no_hp'] ?? ''));
        $idKategori = $payload['id_kategori'] ?? null;

        if ($turnamenId <= 0 || $noHp === '') {
            return null;
        }

        if (! Schema::connection('bornpadel')->hasTable('m_pemain')) {
            return null;
        }

        $pemain = $connection->table('m_pemain')->where('no_hp', $noHp)->first();

        if (! $pemain) {
            return null;
        }

        $resolved = self::resolveKategoriForTournament($connection, $turnamenId, $idKategori, false);
        $kategoriId = ($resolved['ok'] && $resolved['kategori'])
            ? (int) $resolved['kategori']->id
            : null;

        return self::pesertaQueryForPemain($connection, $turnamenId, (int) $pemain->id, $kategoriId)->first();
    }

    private static function storePaymentReceiptFile(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

        if (! in_array($extension, $allowed, true)) {
            throw new \RuntimeException('Bukti bayar harus berformat JPG, PNG, WebP, atau PDF.');
        }

        $filename = uniqid('bayar_', true).'.'.$extension;
        $relativePath = 'img/bukti-bayar/'.$filename;
        $directory = self::bornpadelPublicPath().'/'.dirname($relativePath);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Gagal menyiapkan folder bukti bayar.');
        }

        $file->move($directory, $filename);

        return $relativePath;
    }

    private static function bornpadelPublicPath(): string
    {
        $configured = config('services.bornpadel.public_path');
        if ($configured) {
            return rtrim((string) $configured, '/\\');
        }

        $sibling = realpath(base_path('../bornpadel/public'));
        if ($sibling) {
            return $sibling;
        }

        return public_path();
    }

    public static function paymentReceiptUrl(?string $relativePath): ?string
    {
        return self::bornpadelPublicUrl($relativePath);
    }

    public static function pemainPhotoUrl(?string $relativePath): ?string
    {
        return self::bornpadelPublicUrl($relativePath);
    }

    public static function pemainPhotoPlaceholderUrl(): ?string
    {
        $baseUrl = rtrim((string) config('services.bornpadel.public_url'), '/');

        if ($baseUrl === '') {
            return null;
        }

        return $baseUrl.'/public/img/pemain-placeholder.svg';
    }

    private static function bornpadelPublicUrl(?string $relativePath): ?string
    {
        if (! $relativePath) {
            return null;
        }

        $normalized = str_replace('\\', '/', ltrim($relativePath, '/'));
        $publicRoot = self::bornpadelPublicPath();
        $fullPath = $publicRoot.'/'.$normalized;

        if (! file_exists($fullPath)) {
            return null;
        }

        $baseUrl = rtrim((string) config('services.bornpadel.public_url'), '/');

        if ($baseUrl === '') {
            return null;
        }

        return $baseUrl.'/public/'.$normalized;
    }

    private static function storePemainPhotoFile(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (! in_array($extension, $allowed, true)) {
            throw new \RuntimeException('Foto harus berformat JPG, PNG, atau WebP.');
        }

        $filename = uniqid('pemain_', true).'.'.$extension;
        $relativePath = 'img/pemain/'.$filename;
        $directory = self::bornpadelPublicPath().'/'.dirname($relativePath);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Gagal menyiapkan folder foto pemain.');
        }

        $file->move($directory, $filename);

        return $relativePath;
    }

    public static function canUploadPaymentReceipt(?string $status, ?string $buktiBayarUrl): bool
    {
        if ($buktiBayarUrl) {
            return false;
        }

        return in_array($status, ['unpaid', 'pending', null, ''], true);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{data: array<string, mixed>|null, message: string|null, error: string|null, retry_via_api?: bool}
     */
    private static function registerPlayerFromDatabase(array $payload, ?UploadedFile $foto = null): array
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

            $resolved = self::resolveKategoriForTournament(
                $connection,
                $turnamenId,
                $payload['id_kategori'] ?? null,
                true
            );
            if (! $resolved['ok']) {
                return $fail($resolved['error'] ?? 'Kategori tidak valid.', ! empty($resolved['retry_via_api']));
            }
            $kategori = $resolved['kategori'];
            $kategoriId = $kategori ? (int) $kategori->id : null;

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
                $alreadyRegistered = self::pesertaQueryForPemain(
                    $connection,
                    $turnamenId,
                    (int) $existingPemain->id,
                    $kategoriId
                )->exists();

                if ($alreadyRegistered) {
                    return $fail('Nomor HP sudah terdaftar pada kategori ini.');
                }
            }

            $fotoPath = null;
            if ($foto !== null) {
                try {
                    $fotoPath = self::storePemainPhotoFile($foto);
                } catch (Throwable $e) {
                    return $fail($e->getMessage());
                }
            }

            $now = now();
            $hasKategoriCol = Schema::connection('bornpadel')->hasColumn('turnamen_peserta', 'id_kategori');
            $hasSumberCol = Schema::connection('bornpadel')->hasColumn('turnamen_peserta', 'sumber');

            $result = $connection->transaction(function () use (
                $connection,
                $existingPemain,
                $nama,
                $noHp,
                $gender,
                $rating,
                $tglLahir,
                $usia,
                $fotoPath,
                $turnamenId,
                $kategoriId,
                $hasKategoriCol,
                $hasSumberCol,
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

                if ($fotoPath !== null) {
                    $pemainData['foto'] = $fotoPath;
                }

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

                $pesertaData = [
                    'id_turnamen' => $turnamenId,
                    'id_pemain1' => $pemainId,
                    'status' => 'unpaid',
                    'bukti_bayar' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($hasKategoriCol && $kategoriId !== null) {
                    $pesertaData['id_kategori'] = $kategoriId;
                }

                if ($hasSumberCol) {
                    $pesertaData['sumber'] = 'external';
                }

                $pesertaId = (int) $connection->table('turnamen_peserta')->insertGetId($pesertaData);

                return [
                    'pemain_id' => $pemainId,
                    'peserta_id' => $pesertaId,
                ];
            });

            return [
                'data' => [
                    'turnamen_id' => $turnamenId,
                    'kategori_id' => $kategoriId,
                    'pemain_id' => $result['pemain_id'],
                    'peserta_id' => $result['peserta_id'],
                    'foto_url' => self::pemainPhotoUrl($fotoPath),
                    'status' => 'unpaid',
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
    private static function registerPlayerFromApi(array $payload, ?UploadedFile $foto = null): array
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
            $request = Http::timeout(15)
                ->acceptJson()
                ->withToken($token);

            if ($foto !== null) {
                $request = $request->attach(
                    'foto',
                    file_get_contents($foto->getRealPath()),
                    $foto->getClientOriginalName()
                );
            }

            $response = $request->post($apiUrl.'/register-player', $payload);

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

    /**
     * @param  'GET'|'POST'|'PATCH'|'PUT'  $method
     * @param  array<string, mixed>  $payload
     * @return array{data: array<string, mixed>|null, message: string|null, error: string|null}
     */
    private static function externalJson(
        string $method,
        string $path,
        array $payload = [],
        int $timeout = 15,
        string $fallbackError = 'Gagal memproses permintaan ke server turnamen.'
    ): array {
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
            $request = Http::timeout($timeout)
                ->acceptJson()
                ->withToken($token);

            $url = $apiUrl.'/'.ltrim($path, '/');
            $method = strtoupper($method);

            if ($method !== 'GET') {
                $request = $request->asJson();
            }

            if ($method === 'GET') {
                $response = $request->get($url, $payload);
            } elseif ($method === 'PATCH') {
                $response = $request->patch($url, $payload);
            } elseif ($method === 'PUT') {
                $response = $request->put($url, $payload);
            } else {
                $response = $request->post($url, $payload);
            }

            if ($response->successful() && $response->json('success') === true) {
                $data = $response->json('data');

                return [
                    'data' => is_array($data) ? $data : [],
                    'message' => $response->json('message'),
                    'error' => null,
                ];
            }

            return [
                'data' => null,
                'message' => null,
                'error' => self::formatApiErrorMessage($response, $fallbackError),
            ];
        } catch (Throwable $e) {
            return [
                'data' => null,
                'message' => null,
                'error' => 'Tidak dapat terhubung ke server turnamen.',
            ];
        }
    }

    private static function formatApiErrorMessage($response, string $fallback = 'Gagal mendaftarkan pemain.'): string
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

        return $response->json('message') ?? $fallback;
    }

    /**
     * @return array{data: array<string, mixed>|null, error: string|null}
     */
    public static function fetchMahjongGroups(int $id): array
    {
        $fromDatabase = self::fetchMahjongGroupsFromDatabase($id);
        if ($fromDatabase['error'] === null) {
            return $fromDatabase;
        }

        $fromApi = self::fetchMahjongGroupsFromApi($id);
        if ($fromApi['error'] === null) {
            return $fromApi;
        }

        return [
            'data' => $fromDatabase['data'],
            'error' => $fromDatabase['error'],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findMahjongGroup(int $turnamenId, int $grupId): ?array
    {
        $result = self::fetchMahjongGroups($turnamenId);
        $groups = is_array($result['data']['groups'] ?? null) ? $result['data']['groups'] : [];

        foreach ($groups as $group) {
            if ((int) ($group['id'] ?? 0) === $grupId) {
                return $group;
            }
        }

        return null;
    }

    /**
     * @return array{data: array<string, mixed>|null, error: string|null}
     */
    private static function fetchMahjongGroupsFromDatabase(int $id): array
    {
        try {
            $connection = DB::connection('bornpadel');

            if (! Schema::connection('bornpadel')->hasTable('m_turnamen')
                || ! Schema::connection('bornpadel')->hasTable('grup')
                || ! Schema::connection('bornpadel')->hasTable('grup_member')) {
                return [
                    'data' => null,
                    'error' => 'Database Bornpadel belum memiliki tabel grup.',
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

            $groupRows = $connection->table('grup')
                ->where('id_turnamen', $id)
                ->where('is_aktif', true)
                ->orderBy('nama')
                ->orderBy('id')
                ->get();

            if ($groupRows->isEmpty()) {
                $latestBabak = $connection->table('grup')
                    ->where('id_turnamen', $id)
                    ->max('babak');

                if ($latestBabak) {
                    $groupRows = self::resolveMahjongGrupBatchForBabak($connection, $id, (int) $latestBabak);
                }
            }

            $groups = $groupRows->map(function ($grup) use ($connection, $id) {
                return self::mapMahjongGroupRow($connection, $grup, $id);
            })->values()->all();

            return [
                'data' => [
                    'turnamen' => [
                        'id' => (int) $turnamen->id,
                        'nama' => $turnamen->nama,
                        'jenis' => $turnamen->jenis ?? 'mahjong',
                        'status' => $turnamen->status ?? null,
                        'mahjong_is_final' => (bool) ($turnamen->mahjong_is_final ?? false),
                    ],
                    'groups' => $groups,
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
     * @param  object  $grup
     * @return array<string, mixed>
     */
    private static function mapMahjongGroupRow($connection, $grup, int $turnamenId): array
    {
        $isActive = (bool) ($grup->is_aktif ?? false);
        $babak = (int) ($grup->babak ?? 0);
        $members = self::orderedGroupMembers($connection, (int) $grup->id);

        return [
            'id' => (int) $grup->id,
            'nama' => $grup->nama,
            'babak' => $babak,
            'is_aktif' => $isActive,
            'members' => $members->map(function ($member) use ($connection, $isActive, $babak, $turnamenId) {
                $poinDidapat = self::resolveMahjongBabakPoints(
                    $connection,
                    $member,
                    $babak,
                    $turnamenId,
                    $isActive
                );
                $entries = self::mahjongEntriesForMember($connection, (int) $member->id);

                return [
                    'id_grup_member' => (int) $member->id,
                    'id_pemain' => (int) ($member->id_pemain ?? 0),
                    'id_peserta' => (int) ($member->id_turnamen_peserta ?? 0),
                    'nama' => self::resolveMemberDisplayName($connection, $member),
                    'poin_didapat' => $poinDidapat,
                    'poin_akumulasi' => (int) ($member->poin_akumulasi ?? 0),
                    'total_poin' => self::resolveMahjongTotalPoints($member, $poinDidapat, $isActive),
                    'menang' => self::countMahjongWinsForMember($entries),
                    'entries' => $entries,
                ];
            })->values()->all(),
        ];
    }

    /**
     * @return array<int, array{id:int, poin:int, is_winner:bool}>
     */
    private static function mahjongEntriesForMember($connection, int $memberId): array
    {
        try {
            if (! Schema::connection('bornpadel')->hasTable('mahjong_poin_entry')) {
                return [];
            }

            return $connection->table('mahjong_poin_entry')
                ->where('id_grup_member', $memberId)
                ->orderBy('id')
                ->get()
                ->map(function ($entry) {
                    return [
                        'id' => (int) $entry->id,
                        'poin' => (int) $entry->poin,
                        'is_winner' => (bool) ($entry->is_winner ?? false),
                    ];
                })
                ->values()
                ->all();
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @param  array<int, array{id?:int, poin?:int, is_winner?:bool}>  $entries
     */
    private static function countMahjongWinsForMember(array $entries): int
    {
        $count = 0;

        foreach ($entries as $entry) {
            if (! empty($entry['is_winner'])) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return array{data: array<string, mixed>|null, error: string|null}
     */
    private static function fetchMahjongGroupsFromApi(int $id): array
    {
        $result = self::externalJson(
            'GET',
            'tournaments/'.$id.'/mahjong-groups',
            [],
            15,
            'Gagal memuat grup turnamen.'
        );

        return [
            'data' => $result['data'],
            'error' => $result['error'],
        ];
    }

    /**
     * @param  array<int, array{id_grup_member:int, poin:int}>  $scores
     * @return array{data: array<string, mixed>|null, message: string|null, error: string|null}
     */
    public static function storeGroupScores(int $id, int $idGrup, array $scores, int $winnerMemberId): array
    {
        return self::externalJson(
            'POST',
            'tournaments/'.$id.'/mahjong-scores',
            [
                'id_grup' => $idGrup,
                'id_grup_member_pemenang' => $winnerMemberId,
                'scores' => $scores,
            ],
            15,
            'Gagal menyimpan poin grup.'
        );
    }

    /**
     * @return array{data: array<string, mixed>|null, message: string|null, error: string|null}
     */
    public static function storeMemberScore(int $id, int $idGrupMember, int $poin): array
    {
        return self::externalJson(
            'POST',
            'tournaments/'.$id.'/mahjong-members/'.$idGrupMember.'/scores',
            ['poin' => $poin],
            15,
            'Gagal menyimpan poin pemain.'
        );
    }

    /**
     * @return array{data: array<string, mixed>|null, message: string|null, error: string|null}
     */
    public static function updateScoreEntry(int $id, int $entryId, int $poin): array
    {
        return self::externalJson(
            'PATCH',
            'tournaments/'.$id.'/mahjong-scores/'.$entryId,
            ['poin' => $poin],
            15,
            'Gagal memperbarui poin.'
        );
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

    /**
     * @return array{data: array<string, mixed>|null, error: string|null}
     */
    public static function fetchWinners(int $id): array
    {
        $fromDatabase = self::fetchWinnersFromDatabase($id);
        if ($fromDatabase['error'] === null) {
            return $fromDatabase;
        }

        return self::fetchWinnersFromApi($id);
    }

    /**
     * @return array{data: array<string, mixed>|null, error: string|null}
     */
    private static function fetchWinnersFromDatabase(int $id): array
    {
        try {
            $connection = DB::connection('bornpadel');

            if (! Schema::connection('bornpadel')->hasTable('m_turnamen')
                || ! Schema::connection('bornpadel')->hasTable('turnamen_pemenang')
                || ! Schema::connection('bornpadel')->hasTable('m_pemain')) {
                return [
                    'data' => null,
                    'error' => 'Database Bornpadel belum memiliki tabel juara.',
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

            $winners = $connection->table('turnamen_pemenang')
                ->leftJoin('m_pemain', 'm_pemain.id', '=', 'turnamen_pemenang.id_pemain')
                ->where('turnamen_pemenang.id_turnamen', $id)
                ->orderBy('turnamen_pemenang.peringkat')
                ->get([
                    'turnamen_pemenang.peringkat',
                    'turnamen_pemenang.id_pemain',
                    'turnamen_pemenang.total_poin',
                    'm_pemain.nama',
                    'm_pemain.foto',
                ])
                ->map(function ($row) {
                    return [
                        'peringkat' => (int) $row->peringkat,
                        'label' => 'Juara '.$row->peringkat,
                        'id_pemain' => (int) $row->id_pemain,
                        'nama' => $row->nama,
                        'foto_url' => self::pemainPhotoUrl($row->foto ?? null),
                        'total_poin' => (int) $row->total_poin,
                    ];
                })
                ->all();

            if ($winners === []) {
                return [
                    'data' => null,
                    'error' => 'Data juara belum tersedia.',
                ];
            }

            return [
                'data' => [
                    'turnamen' => [
                        'id' => (int) $turnamen->id,
                        'nama' => $turnamen->nama,
                        'jenis' => $turnamen->jenis ?? 'mahjong',
                        'status' => $turnamen->status ?? null,
                    ],
                    'winners' => $winners,
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
     * @return array{data: array<string, mixed>|null, error: string|null}
     */
    private static function fetchWinnersFromApi(int $id): array
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
                ->get($apiUrl.'/tournaments/'.$id.'/winners');

            if ($response->successful() && $response->json('success') === true) {
                return [
                    'data' => $response->json('data'),
                    'error' => null,
                ];
            }

            return [
                'data' => null,
                'error' => $response->json('message') ?? 'Gagal memuat data juara turnamen.',
            ];
        } catch (Throwable $e) {
            return [
                'data' => null,
                'error' => 'Tidak dapat terhubung ke server turnamen.',
            ];
        }
    }
}
