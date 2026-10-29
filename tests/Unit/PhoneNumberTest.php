<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    public function test_iranian_local_mobile_is_normalized_to_e164(): void
    {
        $this->assertSame('+989121234567', PhoneNumber::normalize('۰۹۱۲ ۱۲۳ ۴۵۶۷'));
        $this->assertSame('+989121234567', PhoneNumber::normalize('09121234567'));
        $this->assertSame('+989121234567', PhoneNumber::normalize('+98 912 123 4567'));
        $this->assertSame('+989121234567', PhoneNumber::normalize('0098 912 123 4567'));
    }

    public function test_phone_format_validation_rejects_incomplete_numbers(): void
    {
        $this->assertTrue(PhoneNumber::isValid('+989121234567'));
        $this->assertFalse(PhoneNumber::isValid('+98'));
        $this->assertFalse(PhoneNumber::isValid(null));
    }
}
