<?php

namespace Tests\Feature;

use App\Models\Meja;
use App\Models\Rental;
use App\Models\RentalMahjongHand;
use App\Models\RentalMahjongPlayer;
use App\Support\RentalMahjongScoring;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Throwable;

class GuestMahjongScoreTest extends TestCase
{
    public function test_guest_skor_auto_winner_edit_and_reset(): void
    {
        $meja = Meja::query()->first();
        $this->assertNotNull($meja);

        DB::beginTransaction();

        try {
            $rental = Rental::query()->create([
                'id_meja' => $meja->id,
                'nama_customer' => '__tmp_guest_skor__',
                'waktu_start' => now(),
                'status' => 'active',
            ]);

            $link = RentalMahjongScoring::ensureScoreLink($rental);
            $token = $link['access_token'];
            $headers = [
                'Accept' => 'application/json',
                'X-Score-Token' => $token,
            ];

            $players = [
                ['seat' => 1, 'nama' => 'Andi'],
                ['seat' => 2, 'nama' => 'Budi'],
                ['seat' => 3, 'nama' => 'Citra'],
                ['seat' => 4, 'nama' => 'Dewi'],
            ];

            $this->withHeaders($headers)
                ->putJson('/guest/skor/players?token='.$token, ['players' => $players])
                ->assertOk();

            $store = $this->withHeaders($headers)
                ->postJson('/guest/skor/hands?token='.$token, [
                    'scores' => [
                        ['seat' => 1, 'poin' => 8],
                        ['seat' => 2, 'poin' => 20],
                        ['seat' => 3, 'poin' => -12],
                        ['seat' => 4, 'poin' => -16],
                    ],
                ])
                ->assertOk()
                ->assertJsonPath('data.hands.0.winner_seat', 2);

            $handId = (int) $store->json('data.hands.0.id');
            $this->assertGreaterThan(0, $handId);

            $this->withHeaders($headers)
                ->putJson('/guest/skor/hands/'.$handId.'?token='.$token, [
                    'scores' => [
                        ['seat' => 1, 'poin' => 30],
                        ['seat' => 2, 'poin' => 4],
                        ['seat' => 3, 'poin' => -14],
                        ['seat' => 4, 'poin' => -20],
                    ],
                ])
                ->assertOk()
                ->assertJsonPath('data.hands.0.winner_seat', 1)
                ->assertJsonPath('data.players.0.total', 30);

            $this->withHeaders($headers)
                ->postJson('/guest/skor/reset?token='.$token, ['reset_players' => false])
                ->assertOk();

            $this->assertSame(0, RentalMahjongHand::query()->where('session_id', $link['session']->id)->count());
            $this->assertSame(4, RentalMahjongPlayer::query()->where('session_id', $link['session']->id)->count());

            $this->withHeaders($headers)
                ->postJson('/guest/skor/reset?token='.$token, ['reset_players' => true])
                ->assertOk();

            $this->assertSame(0, RentalMahjongPlayer::query()->where('session_id', $link['session']->id)->count());
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        DB::rollBack();
    }
}
