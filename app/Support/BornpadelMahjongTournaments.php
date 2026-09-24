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
    public const MAHJONG_TEAM_PLAYERS_PER_TEAM = 4;

    public const MAHJONG_TEAM_MIN_PLAYERS_PER_TEAM = 4;

    public const MAHJONG_TEAM_MAX_PLAYERS_PER_TEAM = 8;

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
                ->whereIn('jenis', self::mahjongJenisValues())
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
                ->whereIn('jenis', self::mahjongJenisValues())
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

            $showGroups = ($turnamen->jenis ?? '') === 'mahjong_team'
                && Schema::connection('bornpadel')->hasTable('turnamen_grup_pendaftaran')
                && Schema::connection('bornpadel')->hasTable('turnamen_grup_pendaftaran_member');

            $select = [
                'turnamen_peserta.id',
                'turnamen_peserta.status',
                'm_pemain.nama',
            ];

            $query = $connection->table('turnamen_peserta')
                ->leftJoin('m_pemain', 'm_pemain.id', '=', 'turnamen_peserta.id_pemain1')
                ->where('turnamen_peserta.id_turnamen', $id)
                ->where('turnamen_peserta.status', '!=', 'rejected')
                ->orderBy('turnamen_peserta.id');

            if ($showGroups) {
                $query->leftJoin(
                    'turnamen_grup_pendaftaran_member',
                    'turnamen_grup_pendaftaran_member.id_peserta',
                    '=',
                    'turnamen_peserta.id'
                )->leftJoin(
                    'turnamen_grup_pendaftaran',
                    'turnamen_grup_pendaftaran.id',
                    '=',
                    'turnamen_grup_pendaftaran_member.id_grup_pendaftaran'
                );
                $select[] = 'turnamen_grup_pendaftaran.id as group_id';
                $select[] = 'turnamen_grup_pendaftaran.nama as group_nama';
                $select[] = 'turnamen_grup_pendaftaran_member.urutan as group_urutan';
            }

            $query->select($select);

            if ($kategoriId !== null
                && Schema::connection('bornpadel')->hasColumn('turnamen_peserta', 'id_kategori')) {
                $query->where('turnamen_peserta.id_kategori', $kategoriId);
            }

            $items = $query->get()->map(function ($row) use ($showGroups) {
                return self::mapPublicParticipantRow($row, $showGroups);
            })->values()->all();

            if ($showGroups) {
                $items = self::sortParticipantsByRegistrationGroup($items);
            }

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
                $mapped = array_map(static function ($item) {
                    return self::mapPublicParticipantRow($item, true);
                }, $items);

                $hasGroups = false;
                foreach ($mapped as $item) {
                    if (! empty($item['group_id'])) {
                        $hasGroups = true;
                        break;
                    }
                }

                if ($hasGroups) {
                    $mapped = self::sortParticipantsByRegistrationGroup($mapped);
                }

                return [
                    'type' => (string) ($data['type'] ?? 'single'),
                    'items' => $mapped,
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
     * @param  object|array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function mapPublicParticipantRow($row, bool $includeGroup = false): array
    {
        $data = is_array($row) ? $row : (array) $row;
        $nama = $data['nama'] ?? $data['display'] ?? $data['label'] ?? null;
        $item = [
            'id' => isset($data['id']) ? (int) $data['id'] : null,
            'nama' => $nama !== null && $nama !== '' ? (string) $nama : '—',
            'status' => $data['status'] ?? null,
        ];

        if (! $includeGroup) {
            return $item;
        }

        $groupId = $data['group_id'] ?? null;
        $item['group_id'] = $groupId !== null && $groupId !== '' ? (int) $groupId : null;
        $item['group_nama'] = ! empty($data['group_nama']) ? (string) $data['group_nama'] : null;
        $item['group_urutan'] = isset($data['group_urutan']) ? (int) $data['group_urutan'] : 0;

        return $item;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private static function sortParticipantsByRegistrationGroup(array $items): array
    {
        usort($items, static function ($a, $b) {
            return strcmp(
                self::participantGroupSortKey($a),
                self::participantGroupSortKey($b)
            );
        });

        foreach ($items as &$item) {
            unset($item['group_urutan']);
        }
        unset($item);

        return array_values($items);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private static function participantGroupSortKey(array $item): string
    {
        $grouped = empty($item['group_id']) ? '1' : '0';
        $groupName = mb_strtolower((string) ($item['group_nama'] ?? ''));
        $urutan = str_pad((string) ((int) ($item['group_urutan'] ?? 0)), 8, '0', STR_PAD_LEFT);
        $playerName = mb_strtolower((string) ($item['nama'] ?? ''));

        return $grouped.'|'.$groupName.'|'.$urutan.'|'.$playerName;
    }

    /**
     * @return array{data: array<string, mixed>|null, error: string|null}
     */
    public static function fetchGroupStandings(int $id, $idKategori = null): array
    {
        $fromDatabase = self::fetchGroupStandingsFromDatabase($id, $idKategori);
        if ($fromDatabase['error'] === null) {
            return $fromDatabase;
        }

        return self::fetchGroupStandingsFromApi($id);
    }

    /**
     * @return array{data: array<string, mixed>|null, error: string|null}
     */
    private static function fetchGroupStandingsFromDatabase(int $id, $idKategori = null): array
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
                ->whereIn('jenis', self::mahjongJenisValues())
                ->first();

            if (! $turnamen) {
                return [
                    'data' => null,
                    'error' => 'Turnamen tidak ditemukan.',
                ];
            }

            if (($turnamen->jenis ?? '') === 'mahjong_team') {
                $teams = self::buildMahjongTeamStandings($connection, $id, $idKategori);

                return [
                    'data' => [
                        'turnamen' => [
                            'id' => (int) $turnamen->id,
                            'nama' => $turnamen->nama,
                            'jenis' => 'mahjong_team',
                            'status' => $turnamen->status ?? null,
                            'mahjong_is_final' => (bool) ($turnamen->mahjong_is_final ?? false),
                        ],
                        'type' => 'mahjong_team',
                        'teams' => $teams,
                        'sections' => [],
                        'overall' => [],
                        'recap' => [],
                        'babak_numbers' => [],
                        'ranking_note' => 'Peringkat berdasarkan total poin tim. Poin tiap pemain tercantum di bawah nama tim.',
                    ],
                    'error' => null,
                ];
            }

            $babakNumbers = self::mahjongGrupQuery($connection, $id, $idKategori)
                ->distinct()
                ->orderByDesc('babak')
                ->pluck('babak');

            $sections = [];

            foreach ($babakNumbers as $babak) {
                $babak = (int) $babak;
                $groups = self::resolveMahjongGrupBatchForBabak($connection, $id, $babak, $idKategori);
                $table = self::buildMahjongBabakTableFromDb($connection, $id, $babak, $idKategori);

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

            $sections = self::annotateMahjongStandingsSections($turnamen, $sections);

            $recapSections = array_map(static function (array $section) {
                return [
                    'babak' => $section['babak'],
                    'is_active' => $section['is_active'],
                    'is_final' => $section['is_final'] ?? false,
                    'advance_kind' => $section['advance_kind'] ?? 'none',
                    'advance_note' => $section['advance_note'] ?? null,
                    'ranking_note' => $section['ranking_note'] ?? null,
                    'rounds' => $section['rounds'],
                    'standings' => $section['recap'] ?? $section['rows'] ?? [],
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
                    'ranking_note' => 'Peringkat berdasarkan Total babak, lalu Menang, lalu Akumulasi.',
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

    private static function mahjongGrupQuery($connection, int $turnamenId, $idKategori = null)
    {
        $query = $connection->table('grup')->where('id_turnamen', $turnamenId);

        if ($idKategori !== null && $idKategori !== ''
            && Schema::connection('bornpadel')->hasColumn('grup', 'id_kategori')) {
            $query->where('id_kategori', (int) $idKategori);
        }

        return $query;
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    private static function resolveMahjongGrupBatchForBabak($connection, int $turnamenId, int $babak, $idKategori = null)
    {
        $active = self::mahjongGrupQuery($connection, $turnamenId, $idKategori)
            ->where('babak', $babak)
            ->where('is_aktif', true)
            ->orderBy('nama')
            ->get();

        if ($active->isNotEmpty()) {
            return $active;
        }

        $latestCreatedAt = self::mahjongGrupQuery($connection, $turnamenId, $idKategori)
            ->where('babak', $babak)
            ->where('is_aktif', false)
            ->max('created_at');

        if (! $latestCreatedAt) {
            return collect();
        }

        return self::mahjongGrupQuery($connection, $turnamenId, $idKategori)
            ->where('babak', $babak)
            ->where('is_aktif', false)
            ->where('created_at', $latestCreatedAt)
            ->orderBy('nama')
            ->get();
    }

    /**
     * @return array{rounds: array<int, array<string, mixed>>, rows: array<int, array<string, mixed>>}
     */
    private static function buildMahjongBabakTableFromDb($connection, int $turnamenId, int $babak, $idKategori = null): array
    {
        $roundBatches = self::getMahjongRoundBatchesForBabak($connection, $turnamenId, $babak, $idKategori);

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

            $totalBabak = array_sum($roundScores) + (int) ($latestMember->poin_penyesuaian ?? 0);

            $rows[] = [
                'id_pemain' => (int) ($latestMember->id_pemain ?? 0),
                'id_peserta' => (int) ($latestMember->id_turnamen_peserta ?? 0),
                'pemain_ids' => self::resolveStandingPemainIds($connection, $latestMember),
                'nama' => self::resolveMemberDisplayName($connection, $latestMember),
                'grup_nama' => $latestMember->_grup_nama ?? null,
                'round_scores' => $roundScores,
                'menang' => self::countMahjongWinsForPeserta($connection, $turnamenId, $babak, $pesertaId),
                'total_babak' => $totalBabak,
                'poin_babak' => $totalBabak,
                'poin_akumulasi' => (int) ($latestMember->poin_akumulasi ?? 0),
                'total_poin' => self::resolveMahjongTotalPoints(
                    $latestMember,
                    $totalBabak,
                    (bool) ($latestMember->_grup_is_aktif ?? false)
                ),
            ];
        }

        usort($rows, [self::class, 'compareMahjongStandingRows']);

        foreach ($rows as $index => &$row) {
            $row['rank'] = $index + 1;
        }
        unset($row);

        return ['rounds' => $rounds, 'rows' => $rows];
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     */
    private static function compareMahjongStandingRows(array $a, array $b): int
    {
        $score = self::compareMahjongStandingScores($a, $b);

        if ($score !== 0) {
            return $score;
        }

        return ((int) ($a['id_peserta'] ?? 0)) <=> ((int) ($b['id_peserta'] ?? 0));
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     */
    private static function compareMahjongStandingScores(array $a, array $b): int
    {
        $totalA = (int) ($a['total_babak'] ?? $a['total_poin'] ?? 0);
        $totalB = (int) ($b['total_babak'] ?? $b['total_poin'] ?? 0);

        if ($totalA !== $totalB) {
            return $totalB <=> $totalA;
        }

        $winsA = (int) ($a['menang'] ?? 0);
        $winsB = (int) ($b['menang'] ?? 0);

        if ($winsA !== $winsB) {
            return $winsB <=> $winsA;
        }

        return ((int) ($b['poin_akumulasi'] ?? 0)) <=> ((int) ($a['poin_akumulasi'] ?? 0));
    }

    /**
     * @param  object  $turnamen
     * @param  array<int, array<string, mixed>>  $sections
     * @return array<int, array<string, mixed>>
     */
    private static function annotateMahjongStandingsSections($turnamen, array $sections): array
    {
        $pesertaIdsByBabak = [];
        $maxBabak = 0;

        foreach ($sections as $section) {
            $babak = (int) ($section['babak'] ?? 0);
            $maxBabak = max($maxBabak, $babak);
            $ids = [];
            foreach ($section['rows'] ?? [] as $row) {
                if (! empty($row['id_peserta'])) {
                    $ids[] = (int) $row['id_peserta'];
                }
            }
            $pesertaIdsByBabak[$babak] = $ids;
        }

        $mahjongIsFinal = (bool) ($turnamen->mahjong_is_final ?? false);

        foreach ($sections as &$section) {
            $babak = (int) ($section['babak'] ?? 0);
            $rows = array_values($section['rows'] ?? []);
            $playerCount = count($rows);
            $isActive = ! empty($section['is_active']);
            $nextBabak = $babak + 1;
            $nextIds = $pesertaIdsByBabak[$nextBabak] ?? null;
            $hasNextBabak = is_array($nextIds) && $nextIds !== [];
            $isFinal = $playerCount > 0
                && $playerCount <= 4
                && ! $hasNextBabak
                && ($isActive || $mahjongIsFinal || $babak === $maxBabak);

            $rankingNote = 'Peringkat berdasarkan Total babak, lalu Menang, lalu Akumulasi.';
            $advanceKind = 'none';
            $advanceNote = null;
            $advanceCount = 0;

            foreach ($rows as &$row) {
                $row['advances'] = false;
                $row['advance_status'] = null;
                $row['is_cutline'] = false;
            }
            unset($row);

            if ($hasNextBabak) {
                $nextSet = array_fill_keys($nextIds, true);
                $lastAdvancingRank = 0;
                foreach ($rows as &$row) {
                    $advances = isset($nextSet[(int) ($row['id_peserta'] ?? 0)]);
                    $row['advances'] = $advances;
                    $row['advance_status'] = $advances ? 'lolos' : null;
                    if ($advances) {
                        $lastAdvancingRank = max($lastAdvancingRank, (int) ($row['rank'] ?? 0));
                    }
                }
                unset($row);
                $rows = self::markMahjongCutline($rows, $lastAdvancingRank, ['lolos']);
                $advanceKind = 'confirmed';
                $advanceCount = 0;
                foreach ($rows as $row) {
                    if (! empty($row['advances'])) {
                        $advanceCount++;
                    }
                }
                $advanceNote = $advanceCount > 0
                    ? 'Lolos ke Babak '.$nextBabak.' ('.$advanceCount.' pemain). Urutan: total babak → menang → akumulasi.'
                    : null;
            } elseif ($isFinal) {
                $advanceKind = 'final';
                foreach ($rows as &$row) {
                    $rank = (int) ($row['rank'] ?? 0);
                    if ($rank === 1) {
                        $row['advance_status'] = 'juara';
                        $row['advances'] = true;
                    } elseif ($rank === 2) {
                        $row['advance_status'] = 'runner_up';
                    } elseif ($rank === 3) {
                        $row['advance_status'] = 'third';
                    }
                }
                unset($row);
                $advanceNote = 'Babak final. Juara ditentukan dari total babak, lalu menang, lalu akumulasi.';
            } elseif ($isActive && $playerCount > 4) {
                $jumlahLolos = self::defaultMahjongAdvanceCount($playerCount);

                if ($jumlahLolos && $jumlahLolos < $playerCount) {
                    $selection = self::resolveMahjongAdvanceQualifiers($rows, $jumlahLolos);
                    $autoIds = [];
                    foreach ($selection['auto_qualified'] ?? [] as $qualified) {
                        if (! empty($qualified['id_peserta'])) {
                            $autoIds[] = (int) $qualified['id_peserta'];
                        }
                    }
                    $contestedIds = [];
                    foreach ($selection['contested'] ?? [] as $qualified) {
                        if (! empty($qualified['id_peserta'])) {
                            $contestedIds[] = (int) $qualified['id_peserta'];
                        }
                    }
                    $qualifierIds = [];
                    foreach ($selection['qualifiers'] ?? [] as $qualified) {
                        if (! empty($qualified['id_peserta'])) {
                            $qualifierIds[] = (int) $qualified['id_peserta'];
                        }
                    }

                    $advanceKind = 'preview';
                    $advanceCount = $jumlahLolos;
                    $nextLabel = $jumlahLolos === 4 ? 'babak final' : 'babak berikutnya';
                    $advanceNote = 'Pratinjau: '.$jumlahLolos.' pemain terbaik lanjut ke '.$nextLabel
                        .' (total). Urutan: total babak → menang → akumulasi.';

                    $entireFieldTied = ($selection['status'] ?? '') === 'needs_tiebreak'
                        && $autoIds === []
                        && count($contestedIds) === $playerCount;

                    if ($entireFieldTied) {
                        $advanceNote .= ' Semua pemain masih seri, jadi garis lolos belum ditandai.';
                    } else {
                        $autoSet = array_fill_keys($autoIds, true);
                        $contestedSet = array_fill_keys($contestedIds, true);
                        $qualifierSet = array_fill_keys($qualifierIds, true);
                        $lastCutlineRank = 0;

                        foreach ($rows as &$row) {
                            $id = (int) ($row['id_peserta'] ?? 0);
                            if (isset($qualifierSet[$id]) || isset($autoSet[$id])) {
                                $row['advances'] = true;
                                $row['advance_status'] = 'pratinjau';
                                $lastCutlineRank = max($lastCutlineRank, (int) ($row['rank'] ?? 0));
                            } elseif (isset($contestedSet[$id])) {
                                $row['advance_status'] = 'seri';
                                $lastCutlineRank = max($lastCutlineRank, (int) ($row['rank'] ?? 0));
                            }
                        }
                        unset($row);

                        $rows = self::markMahjongCutline($rows, $lastCutlineRank, ['pratinjau', 'seri']);

                        if (($selection['status'] ?? '') === 'needs_tiebreak') {
                            $advanceNote .= ' Ada seri di garis lolos — admin memilih saat Akhiri Babak.';
                        }
                    }
                }
            }

            $section['rows'] = $rows;
            $section['recap'] = $rows;
            $section['ranking_note'] = $rankingNote;
            $section['advance_kind'] = $advanceKind;
            $section['advance_note'] = $advanceNote;
            $section['advance_count'] = $advanceCount;
            $section['next_babak'] = $hasNextBabak ? $nextBabak : null;
            $section['is_final'] = $isFinal;
        }
        unset($section);

        usort($sections, static function (array $a, array $b) {
            return ((int) ($b['babak'] ?? 0)) <=> ((int) ($a['babak'] ?? 0));
        });

        return $sections;
    }

    private static function defaultMahjongAdvanceCount(int $playerCount): ?int
    {
        if ($playerCount <= 4) {
            return null;
        }

        $count = (int) (4 * intdiv($playerCount, 8));
        if ($count < 4) {
            $count = 4;
        }
        if ($count >= $playerCount) {
            $count = $playerCount - ($playerCount % 4 === 0 ? 4 : $playerCount % 4);
        }
        if ($count < 4 || $count >= $playerCount || $count % 4 !== 0) {
            return null;
        }

        return $count;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  list<string>  $statuses
     * @return array<int, array<string, mixed>>
     */
    private static function markMahjongCutline(array $rows, int $cutlineRank, array $statuses): array
    {
        if ($cutlineRank <= 0) {
            return $rows;
        }

        foreach ($rows as &$row) {
            $row['is_cutline'] = in_array($row['advance_status'] ?? null, $statuses, true)
                && (int) ($row['rank'] ?? 0) === $cutlineRank;
        }
        unset($row);

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private static function resolveMahjongAdvanceQualifiers(array $rows, int $jumlahLolos): array
    {
        usort($rows, [self::class, 'compareMahjongStandingRows']);

        if ($jumlahLolos <= 0) {
            return [
                'status' => 'resolved',
                'qualifiers' => [],
            ];
        }

        if (count($rows) <= $jumlahLolos) {
            return [
                'status' => 'resolved',
                'qualifiers' => array_values($rows),
            ];
        }

        $autoQualified = [];
        $remaining = $jumlahLolos;
        $index = 0;
        $sorted = array_values($rows);

        while ($index < count($sorted) && $remaining > 0) {
            $anchor = $sorted[$index];
            $bubble = [];

            for ($cursor = $index; $cursor < count($sorted); $cursor++) {
                if (self::compareMahjongStandingScores($sorted[$cursor], $anchor) !== 0) {
                    break;
                }
                $bubble[] = $sorted[$cursor];
            }

            $bubbleSize = count($bubble);

            if ($bubbleSize <= $remaining) {
                $autoQualified = array_merge($autoQualified, $bubble);
                $remaining -= $bubbleSize;
                $index += $bubbleSize;
                continue;
            }

            return [
                'status' => 'needs_tiebreak',
                'auto_qualified' => array_values($autoQualified),
                'contested' => array_values($bubble),
                'slots_remaining' => $remaining,
            ];
        }

        return [
            'status' => 'resolved',
            'qualifiers' => array_values($autoQualified),
        ];
    }

    /**
     * Group grups of a babak into round batches (by the `ronde` column), each
     * batch carrying its members with the parent grup's active flag attached.
     *
     * @return array<int, array<int, object>>
     */
    private static function getMahjongRoundBatchesForBabak($connection, int $turnamenId, int $babak, $idKategori = null): array
    {
        $grups = self::mahjongGrupQuery($connection, $turnamenId, $idKategori)
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
                $member->_grup_nama = $grup->nama ?? null;
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
     * Team standings for Mahjong Tim: active teams ranked by sum of poin_didapat.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function buildMahjongTeamStandings($connection, int $turnamenId, $idKategori = null): array
    {
        $teams = self::mahjongGrupQuery($connection, $turnamenId, $idKategori)
            ->where('is_aktif', true)
            ->orderBy('nama')
            ->orderBy('id')
            ->get();

        $rows = [];

        foreach ($teams as $tim) {
            $members = $connection->table('grup_member')
                ->where('id_grup', $tim->id)
                ->orderByDesc('poin_didapat')
                ->orderBy('id')
                ->get();

            $mappedMembers = [];
            $total = 0;

            foreach ($members as $member) {
                $poin = (int) ($member->poin_didapat ?? 0);
                $total += $poin;
                $mappedMembers[] = [
                    'id' => (int) $member->id,
                    'id_pemain' => ! empty($member->id_pemain) ? (int) $member->id_pemain : null,
                    'nama' => self::resolveMemberDisplayName($connection, $member),
                    'poin_didapat' => $poin,
                ];
            }

            usort($mappedMembers, static function (array $a, array $b) {
                $cmp = ((int) $b['poin_didapat']) <=> ((int) $a['poin_didapat']);

                return $cmp !== 0 ? $cmp : ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
            });

            $rows[] = [
                'id' => (int) $tim->id,
                'id_tim' => (int) $tim->id,
                'nama' => $tim->nama,
                'total_poin' => $total,
                'members' => $mappedMembers,
            ];
        }

        usort($rows, static function (array $a, array $b) {
            $cmp = ((int) $b['total_poin']) <=> ((int) $a['total_poin']);

            return $cmp !== 0 ? $cmp : ((int) ($a['id_tim'] ?? 0)) <=> ((int) ($b['id_tim'] ?? 0));
        });

        foreach ($rows as $index => &$row) {
            $row['rank'] = $index + 1;
        }
        unset($row);

        return array_values($rows);
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
                ->whereIn('jenis', self::mahjongJenisValues())
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
     * @return list<string>
     */
    private static function mahjongJenisValues(): array
    {
        return ['mahjong', 'mahjong_team'];
    }

    private static function isMahjongFormat(?string $jenis): bool
    {
        return in_array($jenis, self::mahjongJenisValues(), true);
    }

    private static function jenisLabel(?string $jenis): string
    {
        return $jenis === 'mahjong_team' ? 'Mahjong Tim' : 'Mahjong';
    }

    public static function allowsGroupRegistration(array $tournament): bool
    {
        return ! empty($tournament['allows_group_registration'])
            || ($tournament['jenis'] ?? null) === 'mahjong_team';
    }

    /**
     * Whether Omahjong may show Input Poin (Bornpadel "API skor eksternal").
     *
     * @param  array<string, mixed>  $tournament
     * @param  array<string, mixed>  $groupsPayload
     */
    public static function canInputPublicScores(array $tournament, $idKategori = null, array $groupsPayload = []): bool
    {
        if (($tournament['status'] ?? null) !== 'ongoing') {
            return false;
        }

        return self::isExternalScoringEnabled($tournament, $idKategori, $groupsPayload);
    }

    /**
     * @param  array<string, mixed>  $tournament
     * @param  array<string, mixed>  $groupsPayload
     */
    public static function isExternalScoringEnabled(array $tournament, $idKategori = null, array $groupsPayload = []): bool
    {
        if ($idKategori !== null && $idKategori !== '') {
            foreach ($tournament['kategori'] ?? [] as $kat) {
                if ((int) ($kat['id'] ?? 0) !== (int) $idKategori) {
                    continue;
                }

                if (array_key_exists('mahjong_external_scoring_enabled', $kat)
                    && $kat['mahjong_external_scoring_enabled'] !== null) {
                    return (bool) $kat['mahjong_external_scoring_enabled'];
                }
            }
        }

        if (array_key_exists('mahjong_external_scoring_enabled', $tournament)
            && $tournament['mahjong_external_scoring_enabled'] !== null) {
            return (bool) $tournament['mahjong_external_scoring_enabled'];
        }

        $fromGroups = $groupsPayload['turnamen']['mahjong_external_scoring_enabled'] ?? null;
        if ($fromGroups !== null) {
            return (bool) $fromGroups;
        }

        return true;
    }

    /**
     * @param  object|array<string, mixed>|null  $row
     */
    private static function readMahjongExternalScoringEnabled($row, bool $default = true): bool
    {
        if (is_array($row)) {
            if (! array_key_exists('mahjong_external_scoring_enabled', $row)
                || $row['mahjong_external_scoring_enabled'] === null) {
                return $default;
            }

            return (bool) $row['mahjong_external_scoring_enabled'];
        }

        if (! is_object($row)
            || ! property_exists($row, 'mahjong_external_scoring_enabled')
            || $row->mahjong_external_scoring_enabled === null) {
            return $default;
        }

        return (bool) $row->mahjong_external_scoring_enabled;
    }

    public static function registrationRosterNoun(array $tournament, bool $titleCase = false): string
    {
        $noun = self::allowsGroupRegistration($tournament)
            ? ($tournament['registration_roster_noun'] ?? 'tim')
            : 'grup';

        return $titleCase ? ucfirst($noun) : $noun;
    }

    public static function registrationRosterSize(array $tournament, $idKategori = null): int
    {
        if (! self::allowsGroupRegistration($tournament)) {
            return 1;
        }

        if ($idKategori !== null && $idKategori !== '') {
            foreach ($tournament['kategori'] ?? [] as $kat) {
                if ((int) ($kat['id'] ?? 0) === (int) $idKategori) {
                    return self::clampMahjongPlayersPerTeam(
                        (int) ($kat['registration_roster_size'] ?? $kat['players_per_group'] ?? $tournament['registration_roster_size'] ?? self::MAHJONG_TEAM_PLAYERS_PER_TEAM)
                    );
                }
            }
        }

        return self::clampMahjongPlayersPerTeam(
            (int) ($tournament['registration_roster_size'] ?? self::MAHJONG_TEAM_PLAYERS_PER_TEAM)
        );
    }

    private static function clampMahjongPlayersPerTeam(int $value): int
    {
        if ($value <= 0) {
            $value = self::MAHJONG_TEAM_PLAYERS_PER_TEAM;
        }

        return max(
            self::MAHJONG_TEAM_MIN_PLAYERS_PER_TEAM,
            min(self::MAHJONG_TEAM_MAX_PLAYERS_PER_TEAM, $value)
        );
    }

    /**
     * @param  object|array<string, mixed>  $row
     */
    private static function readPlayersPerGroup($row): int
    {
        if (is_array($row)) {
            $value = (int) ($row['players_per_group'] ?? 0);
        } else {
            $value = property_exists($row, 'players_per_group')
                ? (int) ($row->players_per_group ?? 0)
                : 0;
        }

        return $value > 0 ? $value : self::MAHJONG_TEAM_PLAYERS_PER_TEAM;
    }

    /**
     * @param  object  $row
     * @return array<string, mixed>
     */
    private static function mapTournamentRow($row): array
    {
        $foto = property_exists($row, 'foto') ? ($row->foto ?? null) : null;
        $jenis = $row->jenis ?? 'mahjong';
        $allowsGroup = $jenis === 'mahjong_team';
        $fallbackPpg = self::readPlayersPerGroup($row);
        $kategoriList = self::listKategoriForTournament((int) $row->id);

        if ($allowsGroup) {
            foreach ($kategoriList as &$kat) {
                $kat['registration_roster_size'] = self::clampMahjongPlayersPerTeam(
                    (int) ($kat['players_per_group'] ?: $fallbackPpg)
                );
            }
            unset($kat);
        }

        $defaultKategori = self::pickDefaultKategori($kategoriList);
        $rosterSize = $allowsGroup
            ? (int) ($defaultKategori['registration_roster_size'] ?? self::clampMahjongPlayersPerTeam($fallbackPpg))
            : 1;

        return [
            'id' => (int) $row->id,
            'nama' => $row->nama,
            'tanggal' => $row->tanggal ?? null,
            'harga' => $defaultKategori['harga'] ?? ($row->harga ?? 0),
            'syarat' => $row->syarat ?? null,
            'jenis' => $jenis,
            'jenis_label' => self::jenisLabel($jenis),
            'status' => $row->status ?? null,
            'mahjong_is_final' => (bool) (
                $defaultKategori['mahjong_is_final']
                ?? ($row->mahjong_is_final ?? false)
            ),
            'mahjong_external_scoring_enabled' => self::readMahjongExternalScoringEnabled(
                $defaultKategori ?? [],
                self::readMahjongExternalScoringEnabled($row, true)
            ),
            // Same as Bornpadel guest landing: turnamen status drives the Daftar button.
            'registration_open' => ($row->status ?? null) === 'open',
            'allows_group_registration' => $allowsGroup,
            'registration_roster_size' => $rosterSize,
            'registration_roster_noun' => $allowsGroup ? 'tim' : 'grup',
            'players_per_group' => $allowsGroup ? $rosterSize : null,
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
                    $playersPerGroup = property_exists($row, 'players_per_group') && $row->players_per_group !== null
                        ? (int) $row->players_per_group
                        : null;

                    return [
                        'id' => (int) $row->id,
                        'nama' => $row->nama,
                        'is_default' => (bool) ($row->is_default ?? false),
                        'urutan' => (int) ($row->urutan ?? 0),
                        'harga' => $row->harga ?? 0,
                        'status' => $row->status ?? null,
                        'mahjong_is_final' => (bool) ($row->mahjong_is_final ?? false),
                        'mahjong_external_scoring_enabled' => self::readMahjongExternalScoringEnabled($row, true),
                        'registration_open' => ($row->status ?? null) === 'open',
                        'players_per_group' => $playersPerGroup,
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
     * Register a full mahjong team (N pemain + nama tim) via the Bornpadel database.
     * There is no external API fallback for this path.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<int, UploadedFile|null>  $fotos
     * @return array{data: array<string, mixed>|null, message: string|null, error: string|null}
     */
    public static function registerGroup(array $payload, array $fotos = []): array
    {
        return self::publicRegisterResult(self::registerGroupFromDatabase($payload, $fotos));
    }

    /**
     * @return array{ok: bool, error: string|null}
     */
    public static function assertGroupNameAvailable(int $turnamenId, string $nama, $idKategori = null): array
    {
        $nama = trim($nama);

        if ($nama === '') {
            return ['ok' => false, 'error' => 'Nama tim wajib diisi.'];
        }

        if (mb_strlen($nama) > 255) {
            return ['ok' => false, 'error' => 'Nama tim maksimal 255 karakter.'];
        }

        try {
            $connection = DB::connection('bornpadel');
            $resolved = self::resolveKategoriForTournament($connection, $turnamenId, $idKategori, false);

            if (! $resolved['ok']) {
                return ['ok' => false, 'error' => $resolved['error'] ?? 'Kategori tidak valid.'];
            }

            $kategoriId = $resolved['kategori'] ? (int) $resolved['kategori']->id : null;
            $lower = mb_strtolower($nama);

            if (Schema::connection('bornpadel')->hasTable('turnamen_grup_pendaftaran')) {
                $query = $connection->table('turnamen_grup_pendaftaran')
                    ->whereRaw('LOWER(nama) = ?', [$lower]);

                if ($kategoriId !== null
                    && Schema::connection('bornpadel')->hasColumn('turnamen_grup_pendaftaran', 'id_kategori')) {
                    $query->where('id_kategori', $kategoriId);
                } else {
                    $query->where('id_turnamen', $turnamenId);
                }

                if ($query->exists()) {
                    return ['ok' => false, 'error' => 'Nama tim sudah digunakan pada kategori ini.'];
                }
            }

            if (Schema::connection('bornpadel')->hasTable('grup')) {
                $query = $connection->table('grup')
                    ->whereRaw('LOWER(nama) = ?', [$lower]);

                if ($kategoriId !== null
                    && Schema::connection('bornpadel')->hasColumn('grup', 'id_kategori')) {
                    $query->where('id_kategori', $kategoriId);
                } else {
                    $query->where('id_turnamen', $turnamenId);
                }

                if ($query->exists()) {
                    return ['ok' => false, 'error' => 'Nama tim sudah digunakan pada kategori ini.'];
                }
            }

            return ['ok' => true, 'error' => null];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'Database Bornpadel: '.$e->getMessage()];
        }
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
                        'group' => null,
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
                    'group' => $peserta ? self::grupPendaftaranForPeserta($connection, (int) $peserta->id) : null,
                ],
                'error' => null,
            ];
        } catch (Throwable $e) {
            return $fail('Database Bornpadel: '.$e->getMessage(), true);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function grupPendaftaranForPeserta($connection, int $pesertaId): ?array
    {
        if ($pesertaId <= 0
            || ! Schema::connection('bornpadel')->hasTable('turnamen_grup_pendaftaran')
            || ! Schema::connection('bornpadel')->hasTable('turnamen_grup_pendaftaran_member')) {
            return null;
        }

        try {
            $member = $connection->table('turnamen_grup_pendaftaran_member')
                ->where('id_peserta', $pesertaId)
                ->first();

            if (! $member) {
                return null;
            }

            return self::grupPendaftaranPayload($connection, (int) $member->id_grup_pendaftaran);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function grupPendaftaranPayload($connection, int $groupId): ?array
    {
        $group = $connection->table('turnamen_grup_pendaftaran')->where('id', $groupId)->first();

        if (! $group) {
            return null;
        }

        $members = $connection->table('turnamen_grup_pendaftaran_member')
            ->join('turnamen_peserta', 'turnamen_peserta.id', '=', 'turnamen_grup_pendaftaran_member.id_peserta')
            ->leftJoin('m_pemain', 'm_pemain.id', '=', 'turnamen_peserta.id_pemain1')
            ->where('turnamen_grup_pendaftaran_member.id_grup_pendaftaran', $groupId)
            ->orderBy('turnamen_grup_pendaftaran_member.urutan')
            ->get([
                'turnamen_grup_pendaftaran_member.urutan',
                'turnamen_peserta.id as peserta_id',
                'turnamen_peserta.status',
                'turnamen_peserta.bukti_bayar',
                'm_pemain.id as pemain_id',
                'm_pemain.nama',
                'm_pemain.gender',
                'm_pemain.no_hp',
                'm_pemain.foto',
            ])
            ->map(function ($row) {
                return [
                    'urutan' => (int) $row->urutan,
                    'peserta_id' => (int) $row->peserta_id,
                    'status' => $row->status,
                    'bukti_bayar_url' => self::paymentReceiptUrl($row->bukti_bayar ?? null),
                    'pemain_id' => $row->pemain_id ? (int) $row->pemain_id : null,
                    'nama' => $row->nama ?: '—',
                    'gender' => $row->gender,
                    'no_hp' => $row->no_hp,
                    'foto_url' => self::pemainPhotoUrl($row->foto ?? null),
                ];
            })
            ->values()
            ->all();

        return [
            'id' => (int) $group->id,
            'nama' => $group->nama,
            'members' => $members,
        ];
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
            $pesertaIds = self::pesertaIdsSharingReceipt($connection, $peserta);
            $now = now();

            $connection->table('turnamen_peserta')
                ->whereIn('id', $pesertaIds)
                ->update([
                    'bukti_bayar' => $storedPath,
                    'updated_at' => $now,
                ]);

            $connection->table('turnamen_peserta')
                ->whereIn('id', $pesertaIds)
                ->whereIn('status', ['unpaid', 'pending'])
                ->update([
                    'status' => 'paid',
                    'updated_at' => $now,
                ]);

            $fresh = $connection->table('turnamen_peserta')->where('id', $peserta->id)->first();
            $status = $fresh->status ?? $peserta->status;

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
     * Peserta IDs that should share one payment receipt (the player plus teammates).
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  object  $peserta
     * @return array<int, int>
     */
    private static function pesertaIdsSharingReceipt($connection, $peserta): array
    {
        $ids = [(int) $peserta->id];
        $group = self::grupPendaftaranForPeserta($connection, (int) $peserta->id);
        if (! $group || empty($group['members']) || ! is_array($group['members'])) {
            return $ids;
        }

        foreach ($group['members'] as $member) {
            if (! empty($member['peserta_id'])) {
                $ids[] = (int) $member['peserta_id'];
            }
        }

        $ids = array_values(array_unique(array_filter($ids)));

        return $ids !== [] ? $ids : [(int) $peserta->id];
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

            if (! self::isMahjongFormat($turnamen->jenis ?? '')) {
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
     * @param  array<int, UploadedFile|null>  $fotos
     * @return array{data: array<string, mixed>|null, message: string|null, error: string|null, retry_via_api?: bool}
     */
    private static function registerGroupFromDatabase(array $payload, array $fotos = []): array
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
                || ! Schema::connection('bornpadel')->hasTable('turnamen_peserta')
                || ! Schema::connection('bornpadel')->hasTable('turnamen_grup_pendaftaran')
                || ! Schema::connection('bornpadel')->hasTable('turnamen_grup_pendaftaran_member')) {
                return $fail('Database Bornpadel belum memiliki tabel pendaftaran tim.');
            }

            $turnamenId = (int) ($payload['id_turnamen'] ?? 0);
            $turnamen = $connection->table('m_turnamen')->where('id', $turnamenId)->first();

            if (! $turnamen) {
                return $fail('Turnamen tidak ditemukan.');
            }

            if (($turnamen->jenis ?? '') !== 'mahjong_team') {
                return $fail('Pendaftaran satu tim hanya tersedia untuk Mahjong Tim.');
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
                return $fail($resolved['error'] ?? 'Kategori tidak valid.');
            }

            $kategori = $resolved['kategori'];
            $kategoriId = $kategori ? (int) $kategori->id : null;
            $fromKat = ($kategori && isset($kategori->players_per_group))
                ? (int) $kategori->players_per_group
                : 0;
            $fromTurnamen = isset($turnamen->players_per_group)
                ? (int) $turnamen->players_per_group
                : 0;
            $expectedSize = self::clampMahjongPlayersPerTeam($fromKat ?: $fromTurnamen);

            $players = is_array($payload['players'] ?? null) ? $payload['players'] : [];
            if (count($players) !== $expectedSize) {
                return $fail('Pendaftaran tim harus berisi tepat '.$expectedSize.' pemain.');
            }

            $namaGrup = trim((string) ($payload['nama_grup'] ?? ''));
            $nameCheck = self::assertGroupNameAvailable($turnamenId, $namaGrup, $kategoriId);
            if (! $nameCheck['ok']) {
                return $fail($nameCheck['error'] ?? 'Nama tim tidak valid.');
            }

            $normalizedPlayers = [];
            $phones = [];

            foreach ($players as $index => $player) {
                $player = is_array($player) ? $player : [];
                $parsed = self::parsePlayerPayload($player, $index + 1);

                if ($parsed['error'] !== null) {
                    return $fail($parsed['error']);
                }

                $noHp = $parsed['no_hp'];
                if (in_array($noHp, $phones, true)) {
                    return $fail('Nomor HP setiap pemain dalam tim harus berbeda satu sama lain.');
                }
                $phones[] = $noHp;

                $existingPemain = $connection->table('m_pemain')->where('no_hp', $noHp)->first();
                if ($existingPemain !== null) {
                    $alreadyRegistered = self::pesertaQueryForPemain(
                        $connection,
                        $turnamenId,
                        (int) $existingPemain->id,
                        $kategoriId
                    )->exists();

                    if ($alreadyRegistered) {
                        return $fail('Nomor HP pemain '.($index + 1).' sudah terdaftar pada kategori ini.');
                    }
                }

                $foto = $fotos[$index] ?? null;
                $fotoPath = null;
                if ($foto instanceof UploadedFile) {
                    try {
                        $fotoPath = self::storePemainPhotoFile($foto);
                    } catch (Throwable $e) {
                        return $fail($e->getMessage());
                    }
                }

                $normalizedPlayers[] = array_merge($parsed, [
                    'existing' => $existingPemain,
                    'foto_path' => $fotoPath,
                ]);
            }

            $now = now();
            $hasKategoriCol = Schema::connection('bornpadel')->hasColumn('turnamen_peserta', 'id_kategori');
            $hasSumberCol = Schema::connection('bornpadel')->hasColumn('turnamen_peserta', 'sumber');
            $hasGroupKategoriCol = Schema::connection('bornpadel')->hasColumn('turnamen_grup_pendaftaran', 'id_kategori');

            $result = $connection->transaction(function () use (
                $connection,
                $normalizedPlayers,
                $namaGrup,
                $turnamenId,
                $kategoriId,
                $hasKategoriCol,
                $hasSumberCol,
                $hasGroupKategoriCol,
                $now
            ) {
                $created = [];

                foreach ($normalizedPlayers as $player) {
                    $pemainData = [
                        'nama' => $player['nama'],
                        'gender' => $player['gender'],
                        'no_hp' => $player['no_hp'],
                        'rating' => $player['rating'],
                        'tgl_lahir' => $player['tgl_lahir'],
                        'usia' => $player['usia'],
                        'updated_at' => $now,
                    ];

                    if ($player['foto_path'] !== null) {
                        $pemainData['foto'] = $player['foto_path'];
                    }

                    if ($player['existing'] !== null) {
                        $connection->table('m_pemain')
                            ->where('id', $player['existing']->id)
                            ->update($pemainData);
                        $pemainId = (int) $player['existing']->id;
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

                    $created[] = [
                        'pemain_id' => $pemainId,
                        'peserta_id' => $pesertaId,
                        'nama' => $player['nama'],
                        'no_hp' => $player['no_hp'],
                        'gender' => $player['gender'],
                        'foto_url' => self::pemainPhotoUrl($player['foto_path']),
                    ];
                }

                $groupData = [
                    'id_turnamen' => $turnamenId,
                    'nama' => $namaGrup,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($hasGroupKategoriCol && $kategoriId !== null) {
                    $groupData['id_kategori'] = $kategoriId;
                }

                $groupId = (int) $connection->table('turnamen_grup_pendaftaran')->insertGetId($groupData);

                foreach ($created as $index => $row) {
                    $connection->table('turnamen_grup_pendaftaran_member')->insert([
                        'id_grup_pendaftaran' => $groupId,
                        'id_peserta' => $row['peserta_id'],
                        'urutan' => $index + 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                return [
                    'group_id' => $groupId,
                    'players' => $created,
                ];
            });

            $first = $result['players'][0] ?? [];

            return [
                'data' => [
                    'turnamen_id' => $turnamenId,
                    'kategori_id' => $kategoriId,
                    'grup_pendaftaran_id' => $result['group_id'],
                    'nama_grup' => $namaGrup,
                    'pemain_id' => $first['pemain_id'] ?? null,
                    'peserta_id' => $first['peserta_id'] ?? null,
                    'foto_url' => $first['foto_url'] ?? null,
                    'status' => 'unpaid',
                    'players' => $result['players'],
                    'group' => self::grupPendaftaranPayload($connection, $result['group_id']),
                ],
                'message' => 'Tim berhasil didaftarkan.',
                'error' => null,
            ];
        } catch (Throwable $e) {
            return $fail('Database Bornpadel: '.$e->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $player
     * @return array{error: string|null, nama: string, no_hp: string, gender: string, rating: float, tgl_lahir: string|null, usia: int|null}
     */
    private static function parsePlayerPayload(array $player, int $index): array
    {
        $label = 'pemain '.$index;
        $nama = trim((string) ($player['nama'] ?? ''));
        if ($nama === '') {
            return ['error' => 'Nama '.$label.' wajib diisi.'] + self::emptyParsedPlayer();
        }

        $noHp = trim((string) ($player['no_hp'] ?? ''));
        if ($noHp === '') {
            return ['error' => 'Nomor HP '.$label.' wajib diisi.'] + self::emptyParsedPlayer();
        }

        if (strlen($noHp) > 20) {
            return ['error' => 'Nomor HP '.$label.' terlalu panjang (maks. 20 karakter).'] + self::emptyParsedPlayer();
        }

        $gender = (string) ($player['gender'] ?? '');
        if (! in_array($gender, ['male', 'female'], true)) {
            return ['error' => 'Jenis kelamin '.$label.' tidak valid.'] + self::emptyParsedPlayer();
        }

        $tglLahir = isset($player['tgl_lahir']) && $player['tgl_lahir'] !== ''
            ? (string) $player['tgl_lahir']
            : null;
        $usia = null;

        if ($tglLahir !== null) {
            try {
                $birthDate = Carbon::parse($tglLahir);
                $tglLahir = $birthDate->toDateString();
                $usia = $birthDate->age;
            } catch (Throwable $e) {
                return ['error' => 'Tanggal lahir '.$label.' tidak valid.'] + self::emptyParsedPlayer();
            }
        }

        return [
            'error' => null,
            'nama' => $nama,
            'no_hp' => $noHp,
            'gender' => $gender,
            'rating' => (float) ($player['rating'] ?? 0),
            'tgl_lahir' => $tglLahir,
            'usia' => $usia,
        ];
    }

    /**
     * @return array{error: string|null, nama: string, no_hp: string, gender: string, rating: float, tgl_lahir: string|null, usia: int|null}
     */
    private static function emptyParsedPlayer(): array
    {
        return [
            'error' => null,
            'nama' => '',
            'no_hp' => '',
            'gender' => '',
            'rating' => 0.0,
            'tgl_lahir' => null,
            'usia' => null,
        ];
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
    public static function fetchMahjongGroups(int $id, $idKategori = null): array
    {
        $fromDatabase = self::fetchMahjongGroupsFromDatabase($id, $idKategori);
        if ($fromDatabase['error'] === null) {
            return self::normalizeMahjongGroupsResult($fromDatabase);
        }

        $fromApi = self::fetchMahjongGroupsFromApi($id);
        if ($fromApi['error'] === null) {
            return self::normalizeMahjongGroupsResult($fromApi);
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
    private static function fetchMahjongGroupsFromDatabase(int $id, $idKategori = null): array
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
                ->whereIn('jenis', self::mahjongJenisValues())
                ->first();

            if (! $turnamen) {
                return [
                    'data' => null,
                    'error' => 'Turnamen tidak ditemukan.',
                ];
            }

            $kategoriId = $idKategori !== null && $idKategori !== ''
                ? (int) $idKategori
                : null;
            $isMahjongTeam = ($turnamen->jenis ?? '') === 'mahjong_team';
            $useMeja = $isMahjongTeam
                && Schema::connection('bornpadel')->hasTable('turnamen_meja')
                && Schema::connection('bornpadel')->hasTable('turnamen_meja_seat');

            if ($useMeja) {
                $groups = self::mapMahjongMejaRows(
                    $connection,
                    $id,
                    self::mahjongMejaQuery($connection, $id, $kategoriId)->where('is_aktif', true)->orderBy('nama')->orderBy('id')->get()
                );

                if ($groups === []) {
                    $latestBabak = self::mahjongMejaQuery($connection, $id, $kategoriId)->max('babak');
                    if ($latestBabak) {
                        $groups = self::mapMahjongMejaRows(
                            $connection,
                            $id,
                            self::mahjongMejaQuery($connection, $id, $kategoriId)
                                ->where('babak', (int) $latestBabak)
                                ->orderByDesc('ronde')
                                ->orderBy('nama')
                                ->orderBy('id')
                                ->get()
                        );
                    }
                }

                $displayedIds = [];
                foreach ($groups as $group) {
                    $displayedIds[(int) ($group['id'] ?? 0)] = true;
                }

                $inactiveMeja = self::mahjongMejaQuery($connection, $id, $kategoriId)
                    ->where('is_aktif', false)
                    ->orderByDesc('babak')
                    ->orderBy('ronde')
                    ->orderBy('nama')
                    ->orderBy('id')
                    ->get()
                    ->filter(function ($meja) use ($displayedIds) {
                        return empty($displayedIds[(int) $meja->id]);
                    })
                    ->values();

                $history = self::buildMahjongHistorySections(
                    self::mapMahjongMejaRows($connection, $id, $inactiveMeja)
                );
                $historyKind = 'meja';
            } else {
                $groupRows = self::mahjongGrupQuery($connection, $id, $kategoriId)
                    ->where('is_aktif', true)
                    ->orderBy('nama')
                    ->orderBy('id')
                    ->get();

                if ($groupRows->isEmpty()) {
                    $latestBabak = self::mahjongGrupQuery($connection, $id, $kategoriId)->max('babak');
                    if ($latestBabak) {
                        $groupRows = self::resolveMahjongGrupBatchForBabak($connection, $id, (int) $latestBabak, $kategoriId);
                    }
                }

                $groups = $groupRows->map(function ($grup) use ($connection, $id) {
                    return self::mapMahjongGroupRow($connection, $grup, $id);
                })->values()->all();

                $displayedIds = [];
                foreach ($groups as $group) {
                    $displayedIds[(int) ($group['id'] ?? 0)] = true;
                }

                $inactiveQuery = self::mahjongGrupQuery($connection, $id, $kategoriId)
                    ->where('is_aktif', false)
                    ->orderByDesc('babak');
                if (Schema::connection('bornpadel')->hasColumn('grup', 'ronde')) {
                    $inactiveQuery->orderBy('ronde');
                }
                $inactiveGroups = $inactiveQuery
                    ->orderBy('nama')
                    ->orderBy('id')
                    ->get()
                    ->filter(function ($grup) use ($displayedIds) {
                        return empty($displayedIds[(int) $grup->id]);
                    })
                    ->map(function ($grup) use ($connection, $id) {
                        return self::mapMahjongGroupRow($connection, $grup, $id);
                    })
                    ->values()
                    ->all();

                $history = self::buildMahjongHistorySections($inactiveGroups);
                $historyKind = 'babak';
            }

            return [
                'data' => [
                    'turnamen' => [
                        'id' => (int) $turnamen->id,
                        'nama' => $turnamen->nama,
                        'jenis' => $turnamen->jenis ?? 'mahjong',
                        'status' => $turnamen->status ?? null,
                        'mahjong_is_final' => (bool) ($turnamen->mahjong_is_final ?? false),
                        'mahjong_external_scoring_enabled' => self::readMahjongExternalScoringEnabled($turnamen, true),
                    ],
                    'groups' => $groups,
                    'history' => $history,
                    'history_kind' => $historyKind,
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
    private static function mapMahjongGroupRow($connection, $grup, int $turnamenId, $mejaId = null): array
    {
        $isActive = (bool) ($grup->is_aktif ?? false);
        $babak = (int) ($grup->babak ?? 0);
        $members = self::orderedGroupMembers($connection, (int) $grup->id);

        $mappedMembers = $members->map(function ($member) use ($connection, $isActive, $babak, $turnamenId, $mejaId) {
            $poinDidapat = self::resolveMahjongBabakPoints(
                $connection,
                $member,
                $babak,
                $turnamenId,
                $isActive
            );
            $entries = self::mahjongEntriesForMember($connection, (int) $member->id, $mejaId);
            $penyesuaian = (int) ($member->poin_penyesuaian ?? 0);

            return [
                'id_grup_member' => (int) $member->id,
                'id_pemain' => (int) ($member->id_pemain ?? 0),
                'id_peserta' => (int) ($member->id_turnamen_peserta ?? 0),
                'nama' => self::resolveMemberDisplayName($connection, $member),
                'tim' => null,
                'poin_didapat' => $poinDidapat,
                'poin_akumulasi' => (int) ($member->poin_akumulasi ?? 0),
                'poin_penyesuaian' => $penyesuaian,
                'total_poin' => self::resolveMahjongTotalPoints($member, $poinDidapat, $isActive),
                'menang' => self::countMahjongWinsForMember($entries),
                'entries' => $entries,
            ];
        })->values()->all();

        $group = [
            'id' => (int) $grup->id,
            'nama' => $grup->nama,
            'babak' => $babak,
            'ronde' => (int) ($grup->ronde ?? 1),
            'is_aktif' => $isActive,
            'kind' => 'grup',
            'members' => $mappedMembers,
        ];

        return self::withMahjongGroupRounds($group);
    }

    private static function mahjongMejaQuery($connection, int $turnamenId, $idKategori = null)
    {
        $query = $connection->table('turnamen_meja')->where('id_turnamen', $turnamenId);

        if ($idKategori !== null && $idKategori !== ''
            && Schema::connection('bornpadel')->hasColumn('turnamen_meja', 'id_kategori')) {
            $query->where('id_kategori', (int) $idKategori);
        }

        return $query;
    }

    /**
     * @param  iterable  $mejaRows
     * @return array<int, array<string, mixed>>
     */
    private static function mapMahjongMejaRows($connection, int $turnamenId, $mejaRows): array
    {
        $mapped = [];

        foreach ($mejaRows as $meja) {
            $mapped[] = self::mapMahjongMejaRow($connection, $meja, $turnamenId);
        }

        return $mapped;
    }

    /**
     * @param  object  $meja
     * @return array<string, mixed>
     */
    private static function mapMahjongMejaRow($connection, $meja, int $turnamenId): array
    {
        $seats = $connection->table('turnamen_meja_seat')
            ->join('grup_member', 'grup_member.id', '=', 'turnamen_meja_seat.id_grup_member')
            ->leftJoin('grup', 'grup.id', '=', 'grup_member.id_grup')
            ->where('turnamen_meja_seat.id_meja', $meja->id)
            ->orderBy('turnamen_meja_seat.seat_order')
            ->orderBy('turnamen_meja_seat.id')
            ->get([
                'grup_member.*',
                'grup.nama as tim_nama',
                'turnamen_meja_seat.seat_order',
            ]);

        $isActive = (bool) ($meja->is_aktif ?? false);
        $babak = (int) ($meja->babak ?? 0);
        $mejaId = (int) $meja->id;

        $mappedMembers = $seats->map(function ($member) use ($connection, $isActive, $babak, $turnamenId, $mejaId) {
            $poinDidapat = self::resolveMahjongBabakPoints(
                $connection,
                $member,
                $babak,
                $turnamenId,
                $isActive
            );
            $entries = self::mahjongEntriesForMember($connection, (int) $member->id, $mejaId);

            return [
                'id_grup_member' => (int) $member->id,
                'id_pemain' => (int) ($member->id_pemain ?? 0),
                'id_peserta' => (int) ($member->id_turnamen_peserta ?? 0),
                'nama' => self::resolveMemberDisplayName($connection, $member),
                'tim' => $member->tim_nama ?? null,
                'poin_didapat' => $poinDidapat,
                'poin_akumulasi' => (int) ($member->poin_akumulasi ?? 0),
                'poin_penyesuaian' => (int) ($member->poin_penyesuaian ?? 0),
                'total_poin' => self::resolveMahjongTotalPoints($member, $poinDidapat, $isActive),
                'menang' => self::countMahjongWinsForMember($entries),
                'entries' => $entries,
            ];
        })->values()->all();

        $group = [
            'id' => $mejaId,
            'id_meja' => $mejaId,
            'nama' => $meja->nama,
            'babak' => $babak,
            'ronde' => (int) ($meja->ronde ?? 1),
            'is_aktif' => $isActive,
            'kind' => 'meja',
            'members' => $mappedMembers,
        ];

        return self::withMahjongGroupRounds($group);
    }

    /**
     * @param  array{data?: array<string, mixed>|null, error: string|null}  $result
     * @return array{data: array<string, mixed>|null, error: string|null}
     */
    private static function normalizeMahjongGroupsResult(array $result): array
    {
        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $groups = is_array($data['groups'] ?? null) ? $data['groups'] : [];
        $normalized = [];

        foreach ($groups as $group) {
            $normalized[] = self::withMahjongGroupRounds(is_array($group) ? $group : []);
        }

        $data['groups'] = $normalized;
        $data['history'] = is_array($data['history'] ?? null) ? $data['history'] : [];
        $data['history_kind'] = $data['history_kind'] ?? (
            (($data['turnamen']['jenis'] ?? null) === 'mahjong_team') ? 'meja' : 'babak'
        );
        $result['data'] = $data;

        return $result;
    }

    /**
     * @param  array<string, mixed>  $group
     * @return array<string, mixed>
     */
    private static function withMahjongGroupRounds(array $group): array
    {
        $members = is_array($group['members'] ?? null) ? $group['members'] : [];
        $normalizedMembers = [];

        foreach ($members as $member) {
            $member = is_array($member) ? $member : [];
            $member['id_grup_member'] = (int) ($member['id_grup_member'] ?? $member['id'] ?? 0);
            $member['poin_penyesuaian'] = (int) ($member['poin_penyesuaian'] ?? 0);
            $member['entries'] = is_array($member['entries'] ?? null) ? $member['entries'] : [];
            $member['menang'] = isset($member['menang'])
                ? (int) $member['menang']
                : self::countMahjongWinsForMember($member['entries']);
            $normalizedMembers[] = $member;
        }

        $group['members'] = $normalizedMembers;
        $group['rounds'] = self::buildMahjongScoreRounds($normalizedMembers);
        $group['ronde'] = (int) ($group['ronde'] ?? 1);
        $group['kind'] = $group['kind'] ?? (! empty($group['id_meja']) ? 'meja' : 'grup');

        return $group;
    }

    /**
     * @param  array<int, array<string, mixed>>  $members
     * @return array<int, array<int, array<string, mixed>|null>>
     */
    private static function buildMahjongScoreRounds(array $members): array
    {
        $memberIds = [];
        foreach ($members as $member) {
            $id = (int) ($member['id_grup_member'] ?? 0);
            if ($id > 0) {
                $memberIds[] = $id;
            }
        }

        if ($memberIds === []) {
            return [];
        }

        $items = [];
        foreach ($members as $member) {
            $memberId = (int) ($member['id_grup_member'] ?? 0);
            foreach ($member['entries'] ?? [] as $entry) {
                if (! is_array($entry)) {
                    continue;
                }
                $items[] = [
                    'member_id' => $memberId,
                    'entry' => $entry,
                    'ts' => (int) ($entry['created_at'] ?? 0),
                    'id' => (int) ($entry['id'] ?? 0),
                ];
            }
        }

        if ($items === []) {
            return [];
        }

        $hasTimestamps = false;
        foreach ($items as $item) {
            if ($item['ts'] > 0) {
                $hasTimestamps = true;
                break;
            }
        }

        if (! $hasTimestamps) {
            $byMember = [];
            $max = 0;
            foreach ($members as $member) {
                $id = (int) ($member['id_grup_member'] ?? 0);
                $entries = array_values($member['entries'] ?? []);
                $byMember[$id] = $entries;
                $max = max($max, count($entries));
            }

            $rounds = [];
            for ($i = 0; $i < $max; $i++) {
                $round = [];
                foreach ($memberIds as $id) {
                    $round[] = $byMember[$id][$i] ?? null;
                }
                $rounds[] = $round;
            }

            return $rounds;
        }

        usort($items, static function ($a, $b) {
            if ($a['ts'] === $b['ts']) {
                return $a['id'] <=> $b['id'];
            }

            return $a['ts'] <=> $b['ts'];
        });

        $rounds = [];
        $used = [];
        foreach ($items as $item) {
            $itemId = $item['id'] > 0 ? $item['id'] : spl_object_hash((object) $item);
            if (isset($used[$itemId])) {
                continue;
            }

            $roundMap = [];
            foreach ($memberIds as $id) {
                $roundMap[$id] = null;
            }
            $roundMap[$item['member_id']] = $item['entry'];
            $used[$itemId] = true;

            foreach ($items as $otherIndex => $other) {
                $otherId = $other['id'] > 0 ? $other['id'] : ('i'.$otherIndex);
                if (isset($used[$otherId])) {
                    continue;
                }
                if (($roundMap[$other['member_id']] ?? null) !== null) {
                    continue;
                }
                if (abs($other['ts'] - $item['ts']) <= 3) {
                    $roundMap[$other['member_id']] = $other['entry'];
                    $used[$otherId] = true;
                }
            }

            $round = [];
            foreach ($memberIds as $id) {
                $round[] = $roundMap[$id] ?? null;
            }
            $rounds[] = $round;
        }

        return $rounds;
    }

    /**
     * @param  array<int, array<string, mixed>>  $groups
     * @return array<int, array<string, mixed>>
     */
    private static function buildMahjongHistorySections(array $groups): array
    {
        $byBabak = [];

        foreach ($groups as $group) {
            $babak = (int) ($group['babak'] ?? 1);
            if ($babak <= 0) {
                $babak = 1;
            }
            $ronde = (int) ($group['ronde'] ?? 1);
            if ($ronde <= 0) {
                $ronde = 1;
            }
            $byBabak[$babak][$ronde][] = $group;
        }

        krsort($byBabak, SORT_NUMERIC);

        $sections = [];
        foreach ($byBabak as $babak => $rondes) {
            ksort($rondes, SORT_NUMERIC);
            $rondeList = [];
            foreach ($rondes as $ronde => $rondeGroups) {
                $rondeList[] = [
                    'ronde' => (int) $ronde,
                    'groups' => array_values($rondeGroups),
                ];
            }
            $sections[] = [
                'babak' => (int) $babak,
                'rondes' => $rondeList,
            ];
        }

        return $sections;
    }

    /**
     * @return array<int, array{id:int, poin:int, is_winner:bool}>
     */
    private static function mahjongEntriesForMember($connection, int $memberId, $mejaId = null): array
    {
        try {
            if (! Schema::connection('bornpadel')->hasTable('mahjong_poin_entry')) {
                return [];
            }

            $query = $connection->table('mahjong_poin_entry')
                ->where('id_grup_member', $memberId);

            if ($mejaId !== null
                && Schema::connection('bornpadel')->hasColumn('mahjong_poin_entry', 'id_meja')) {
                $query->where('id_meja', (int) $mejaId);
            }

            $columns = ['id', 'poin', 'is_winner'];
            if (Schema::connection('bornpadel')->hasColumn('mahjong_poin_entry', 'created_at')) {
                $columns[] = 'created_at';
            }

            return $query->orderBy('id')
                ->get($columns)
                ->map(function ($entry) {
                    $createdAt = 0;
                    if (! empty($entry->created_at)) {
                        try {
                            $createdAt = Carbon::parse($entry->created_at)->getTimestamp();
                        } catch (Throwable $e) {
                            $createdAt = 0;
                        }
                    }

                    return [
                        'id' => (int) $entry->id,
                        'poin' => (int) $entry->poin,
                        'is_winner' => (bool) ($entry->is_winner ?? false),
                        'created_at' => $createdAt,
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
    public static function storeGroupScores(int $id, int $idGrup, array $scores, ?int $winnerMemberId = null): array
    {
        $payload = [
            'id_grup' => $idGrup,
            'scores' => $scores,
        ];

        if ($winnerMemberId !== null) {
            $payload['id_grup_member_pemenang'] = $winnerMemberId;
        }

        return self::externalJson(
            'POST',
            'tournaments/'.$id.'/mahjong-scores',
            $payload,
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
                $data = $response->json('data');
                $data = is_array($data) ? $data : [];

                if ((($data['turnamen']['jenis'] ?? null) === 'mahjong_team')
                    && empty($data['teams'])
                    && isset($data['groups'])
                    && is_array($data['groups'])) {
                    $data['type'] = 'mahjong_team';
                    $data['teams'] = $data['groups'];
                    $data['sections'] = $data['sections'] ?? [];
                    $data['ranking_note'] = $data['ranking_note']
                        ?? 'Peringkat berdasarkan total poin tim. Poin tiap pemain tercantum di bawah nama tim.';
                }

                return [
                    'data' => $data,
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
                ->whereIn('jenis', self::mahjongJenisValues())
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
