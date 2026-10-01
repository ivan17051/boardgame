<?php

namespace Tests\Unit;

use App\Support\BornpadelMahjongTournaments;
use Tests\TestCase;

class RegistrationStatusSplitTest extends TestCase
{
    public function test_legacy_paid_status_splits_into_pending_and_paid(): void
    {
        $this->assertSame(
            ['status' => 'pending', 'payment_status' => 'paid'],
            BornpadelMahjongTournaments::normalizeRegistrationState('paid')
        );
    }

    public function test_legacy_unpaid_status_splits_into_pending_and_unpaid(): void
    {
        $this->assertSame(
            ['status' => 'pending', 'payment_status' => 'unpaid'],
            BornpadelMahjongTournaments::normalizeRegistrationState('unpaid')
        );
    }

    public function test_approved_status_keeps_explicit_payment_status(): void
    {
        $this->assertSame(
            ['status' => 'approved', 'payment_status' => 'paid'],
            BornpadelMahjongTournaments::normalizeRegistrationState('approved', 'paid')
        );
    }

    public function test_receipt_marks_unknown_payment_as_paid(): void
    {
        $this->assertSame(
            ['status' => 'pending', 'payment_status' => 'paid'],
            BornpadelMahjongTournaments::normalizeRegistrationState('pending', null, true)
        );
    }
}
