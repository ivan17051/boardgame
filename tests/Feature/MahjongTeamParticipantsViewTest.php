<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Throwable;

class MahjongTeamParticipantsViewTest extends TestCase
{
    public function test_mahjong_team_participants_are_grouped_by_team(): void
    {
        $html = view('public.mahjong-participants', [
            'tournament' => [
                'id' => 1,
                'nama' => 'Mahjong Tim Cup',
                'jenis' => 'mahjong_team',
                'allows_group_registration' => true,
                'status' => 'open',
            ],
            'idKategori' => null,
            'participants' => [
                [
                    'id' => 1,
                    'nama' => 'Andi',
                    'status' => 'approved',
                    'group_id' => 10,
                    'group_nama' => 'Dragon Squad',
                ],
                [
                    'id' => 2,
                    'nama' => 'Budi',
                    'status' => 'approved',
                    'group_id' => 10,
                    'group_nama' => 'Dragon Squad',
                ],
                [
                    'id' => 3,
                    'nama' => 'Citra',
                    'status' => 'approved',
                    'group_id' => 11,
                    'group_nama' => 'Tiger Clan',
                ],
                [
                    'id' => 4,
                    'nama' => 'Dewi',
                    'status' => 'paid',
                    'group_id' => null,
                    'group_nama' => null,
                ],
            ],
            'participantType' => 'single',
            'participantsError' => null,
        ])->render();

        $this->assertStringContainsString('Dragon Squad', $html);
        $this->assertStringContainsString('Tiger Clan', $html);
        $this->assertStringContainsString('Andi', $html);
        $this->assertStringContainsString('Citra', $html);
        $this->assertStringContainsString('Dewi', $html);
        $this->assertStringContainsString('Individu / Belum berkelompok', $html);

        $dragonPos = strpos($html, 'Dragon Squad');
        $tigerPos = strpos($html, 'Tiger Clan');
        $soloPos = strpos($html, 'Individu / Belum berkelompok');
        $this->assertNotFalse($dragonPos);
        $this->assertNotFalse($tigerPos);
        $this->assertNotFalse($soloPos);
        $this->assertLessThan($soloPos, min($dragonPos, $tigerPos));
    }

    public function test_regular_mahjong_participants_are_not_grouped(): void
    {
        $html = view('public.mahjong-participants', [
            'tournament' => [
                'id' => 2,
                'nama' => 'Mahjong Cup',
                'jenis' => 'mahjong',
                'status' => 'open',
            ],
            'idKategori' => null,
            'participants' => [
                [
                    'id' => 1,
                    'nama' => 'Andi',
                    'status' => 'approved',
                ],
            ],
            'participantType' => 'single',
            'participantsError' => null,
        ])->render();

        $this->assertStringContainsString('Andi', $html);
        $this->assertStringNotContainsString('Individu / Belum berkelompok', $html);
        $this->assertStringNotContainsString('class="participant-group-row"', $html);
        $this->assertStringNotContainsString('guest-tournament-nav__label">Klasemen', $html);
    }

    public function test_klasemen_tab_is_hidden_when_tournament_is_open(): void
    {
        $openHtml = view('public.mahjong-participants', [
            'tournament' => [
                'id' => 3,
                'nama' => 'Open Cup',
                'jenis' => 'mahjong',
                'status' => 'open',
            ],
            'idKategori' => null,
            'participants' => [],
            'participantType' => 'single',
            'participantsError' => null,
        ])->render();

        $ongoingHtml = view('public.mahjong-participants', [
            'tournament' => [
                'id' => 4,
                'nama' => 'Ongoing Cup',
                'jenis' => 'mahjong',
                'status' => 'ongoing',
            ],
            'idKategori' => null,
            'participants' => [],
            'participantType' => 'single',
            'participantsError' => null,
        ])->render();

        $this->assertStringNotContainsString('guest-tournament-nav__label">Klasemen', $openHtml);
        $this->assertStringContainsString('guest-tournament-nav__label">Klasemen', $ongoingHtml);
    }

    public function test_peserta_page_groups_mahjong_team_players_from_bornpadel(): void
    {
        $bp = DB::connection('bornpadel');
        $bp->beginTransaction();

        try {
            $now = now();
            $turnamenId = $bp->table('m_turnamen')->insertGetId([
                'nama' => '__tmp_mj_team_group_check__',
                'harga' => 0,
                'jenis' => 'mahjong_team',
                'status' => 'open',
                'mahjong_is_final' => 0,
                'mahjong_external_scoring_enabled' => 1,
                'doc' => $now,
                'dom' => $now,
            ]);

            $kategoriId = $bp->table('turnamen_kategori')->insertGetId([
                'id_turnamen' => $turnamenId,
                'nama' => 'Open',
                'is_default' => 1,
                'urutan' => 1,
                'harga' => 0,
                'status' => 'open',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $players = [
                ['nama' => 'Andi Dragon', 'team' => 'Dragon Squad', 'urutan' => 1],
                ['nama' => 'Budi Dragon', 'team' => 'Dragon Squad', 'urutan' => 2],
                ['nama' => 'Citra Tiger', 'team' => 'Tiger Clan', 'urutan' => 1],
                ['nama' => 'Dewi Solo', 'team' => null, 'urutan' => 0],
            ];

            $teamIds = [];
            foreach ($players as $index => $player) {
                $pemainId = $bp->table('m_pemain')->insertGetId([
                    'nama' => $player['nama'],
                    'gender' => 'male',
                    'no_hp' => '+62819'.str_pad((string) (8000000 + $index), 7, '0', STR_PAD_LEFT),
                    'rating' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $pesertaId = $bp->table('turnamen_peserta')->insertGetId([
                    'id_turnamen' => $turnamenId,
                    'id_kategori' => $kategoriId,
                    'id_pemain1' => $pemainId,
                    'status' => 'approved',
                    'sumber' => 'internal',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ($player['team'] === null) {
                    continue;
                }

                if (! isset($teamIds[$player['team']])) {
                    $teamIds[$player['team']] = $bp->table('turnamen_grup_pendaftaran')->insertGetId([
                        'id_turnamen' => $turnamenId,
                        'id_kategori' => $kategoriId,
                        'nama' => $player['team'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $bp->table('turnamen_grup_pendaftaran_member')->insert([
                    'id_grup_pendaftaran' => $teamIds[$player['team']],
                    'id_peserta' => $pesertaId,
                    'urutan' => $player['urutan'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $html = $this->get('/turnamen/mahjong/'.$turnamenId.'/peserta')
                ->assertOk()
                ->getContent();

            $this->assertStringContainsString('Dragon Squad', $html);
            $this->assertStringContainsString('Tiger Clan', $html);
            $this->assertStringContainsString('Andi Dragon', $html);
            $this->assertStringContainsString('Citra Tiger', $html);
            $this->assertStringContainsString('Dewi Solo', $html);
            $this->assertStringContainsString('Individu / Belum berkelompok', $html);
            $this->assertStringNotContainsString('guest-tournament-nav__label">Klasemen', $html);

            $dragonPos = strpos($html, 'Dragon Squad');
            $tigerPos = strpos($html, 'Tiger Clan');
            $soloPos = strpos($html, 'Individu / Belum berkelompok');
            $this->assertNotFalse($dragonPos);
            $this->assertNotFalse($tigerPos);
            $this->assertNotFalse($soloPos);
            $this->assertLessThan($soloPos, min($dragonPos, $tigerPos));
        } catch (Throwable $e) {
            $bp->rollBack();
            throw $e;
        }

        $bp->rollBack();
    }
}
