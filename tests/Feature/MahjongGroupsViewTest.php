<?php

namespace Tests\Feature;

use Tests\TestCase;

class MahjongGroupsViewTest extends TestCase
{
    public function test_grup_tab_renders_round_table_and_riwayat(): void
    {
        $html = view('public.mahjong-groups', [
            'tournament' => [
                'id' => 9,
                'nama' => 'Mahjong Cup',
                'jenis' => 'mahjong',
                'status' => 'ongoing',
            ],
            'idKategori' => null,
            'groups' => [
                [
                    'id' => 21,
                    'nama' => 'Grup A',
                    'babak' => 2,
                    'ronde' => 1,
                    'kind' => 'grup',
                    'members' => [
                        [
                            'id_grup_member' => 1,
                            'nama' => 'Andi',
                            'poin_didapat' => 20,
                            'poin_akumulasi' => 10,
                            'poin_penyesuaian' => 0,
                            'total_poin' => 30,
                            'menang' => 1,
                        ],
                        [
                            'id_grup_member' => 2,
                            'nama' => 'Budi',
                            'poin_didapat' => -20,
                            'poin_akumulasi' => 8,
                            'poin_penyesuaian' => 0,
                            'total_poin' => -12,
                            'menang' => 0,
                        ],
                    ],
                    'rounds' => [
                        [
                            ['id' => 11, 'poin' => 20, 'is_winner' => true],
                            ['id' => 12, 'poin' => -20, 'is_winner' => false],
                        ],
                    ],
                ],
            ],
            'groupHistory' => [
                [
                    'babak' => 1,
                    'rondes' => [
                        [
                            'ronde' => 1,
                            'groups' => [
                                [
                                    'id' => 11,
                                    'nama' => 'Grup Lama',
                                    'babak' => 1,
                                    'ronde' => 1,
                                    'kind' => 'grup',
                                    'members' => [
                                        [
                                            'id_grup_member' => 3,
                                            'nama' => 'Citra',
                                            'poin_didapat' => 4,
                                            'poin_akumulasi' => 0,
                                            'total_poin' => 4,
                                            'menang' => 0,
                                            'entries' => [],
                                        ],
                                    ],
                                    'rounds' => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'groupHistoryKind' => 'babak',
            'groupsError' => null,
            'canInputScores' => false,
            'scoreStoreUrl' => '/turnamen/mahjong/9/poin',
        ])->render();

        $this->assertStringContainsString('Grup A', $html);
        $this->assertStringContainsString('Andi', $html);
        $this->assertStringContainsString('Ronde', $html);
        $this->assertStringContainsString('+20', $html);
        $this->assertStringContainsString('Subtotal', $html);
        $this->assertStringContainsString('Riwayat Babak', $html);
        $this->assertStringContainsString('Grup Lama', $html);
        $this->assertStringContainsString('Babak 1', $html);
    }
}
