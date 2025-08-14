<?php

namespace Tests\Unit;

use App\Http\Controllers\DonateController;
use Tests\TestCase;

class IyzicoPaymentServiceTest extends TestCase
{
    public function test_amount_parser(): void
    {
        $this->assertSame(12345, DonateController::parseAmountToMinor('123,45'));
        $this->assertSame(12345, DonateController::parseAmountToMinor('123.45'));
        $this->assertSame(10000, DonateController::parseAmountToMinor('100'));
        $this->assertSame(100, DonateController::parseAmountToMinor('1'));
    }
}



