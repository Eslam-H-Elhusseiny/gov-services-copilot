<?php

namespace Tests\Unit;

use App\Domain\Copilot\Document\TextCleaner;
use PHPUnit\Framework\TestCase;

class TextCleanerTest extends TestCase
{
    private function cleaner(): TextCleaner
    {
        return new TextCleaner;
    }

    public function test_valid_utf8_is_unchanged(): void
    {
        $text = 'Valid UTF-8: café — مرحبا';
        $this->assertSame($text, $this->cleaner()->clean($text));
    }

    public function test_arabic_is_unchanged(): void
    {
        $text = 'المواطن يفقد بطاقة الهوية الوطنية';
        $this->assertSame($text, $this->cleaner()->clean($text));
    }

    public function test_windows_1252_bytes_are_converted(): void
    {
        $cleaned = $this->cleaner()->clean("Cost \x80\x94 5");

        $this->assertTrue(mb_check_encoding($cleaned, 'UTF-8'));
        $this->assertStringContainsString('€', $cleaned);
        $this->assertStringContainsString('”', $cleaned);
    }

    public function test_mixed_arabic_and_windows_1252_is_preserved(): void
    {
        $cleaned = $this->cleaner()->clean("مرحبا \x80 العالم");

        $this->assertTrue(mb_check_encoding($cleaned, 'UTF-8'));
        $this->assertStringContainsString('مرحبا', $cleaned);
        $this->assertStringContainsString('€', $cleaned);
    }

    public function test_line_endings_are_normalized(): void
    {
        $this->assertSame("a\nb\nc", $this->cleaner()->clean("a\r\nb\rc"));
    }

    public function test_surrounding_whitespace_is_trimmed(): void
    {
        $this->assertSame('hi', $this->cleaner()->clean("  \n hi \n "));
    }

    public function test_cleaning_is_idempotent(): void
    {
        $cleaner = $this->cleaner();
        $once = $cleaner->clean("مرحبا \x80 — café");
        $twice = $cleaner->clean($once);

        $this->assertSame($once, $twice);
    }

    public function test_output_is_always_valid_utf8(): void
    {
        $cleaned = $this->cleaner()->clean("mixed \x80 text — café مرحبا");
        $this->assertTrue(mb_check_encoding($cleaned, 'UTF-8'));
    }
}
