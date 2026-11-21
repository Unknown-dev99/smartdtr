<?php

namespace Tests\Unit;

use App\Services\SmsService;
use PHPUnit\Framework\TestCase;

class SmsServiceTest extends TestCase
{
    public function test_normalizes_philippine_mobile_numbers(): void
    {
        $sms = new SmsService;

        $this->assertSame('+639171234567', $sms->normalizePhone('09171234567'));
        $this->assertSame('+639171234567', $sms->normalizePhone('+639171234567'));
        $this->assertNull($sms->normalizePhone('123'));
    }
}
