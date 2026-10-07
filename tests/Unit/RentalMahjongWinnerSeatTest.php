<?php

namespace Tests\Unit;

use App\Support\RentalMahjongScoring;
use Tests\TestCase;

class RentalMahjongWinnerSeatTest extends TestCase
{
    public function test_unique_highest_score_is_the_winner(): void
    {
        $this->assertSame(2, RentalMahjongScoring::winnerSeatFromScores([
            1 => 4,
            2 => 12,
            3 => -8,
            4 => -8,
        ]));
    }

    public function test_tied_highest_scores_have_no_winner(): void
    {
        $this->assertNull(RentalMahjongScoring::winnerSeatFromScores([
            1 => 10,
            2 => 10,
            3 => 0,
            4 => -20,
        ]));
    }

    public function test_preferred_seat_wins_only_when_tied_for_highest(): void
    {
        $this->assertSame(2, RentalMahjongScoring::winnerSeatFromScores([
            1 => 10,
            2 => 10,
            3 => 0,
            4 => -20,
        ], 2));

        $this->assertSame(3, RentalMahjongScoring::winnerSeatFromScores([
            1 => 4,
            2 => 4,
            3 => 12,
            4 => 0,
        ], 1));
    }
}
