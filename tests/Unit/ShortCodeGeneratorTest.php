<?php

use PHPUnit\Framework\TestCase;

class ShortCodeGeneratorTest extends TestCase
{
    public function testGenerateCreatesValidCode(): void
    {
        $code = ShortCodeGenerator::generate();

        $this->assertNotEmpty($code);
        $this->assertGreaterThanOrEqual(6, strlen($code));
        // Should only contain base62 characters
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $code);
    }

    public function testGenerateProducesUniqueCode(): void
    {
        $code1 = ShortCodeGenerator::generate();
        $code2 = ShortCodeGenerator::generate();

        $this->assertNotEquals($code1, $code2);
    }

    public function testGenerateWithMinLength(): void
    {
        $code = ShortCodeGenerator::generate(length: 10);

        $this->assertGreaterThanOrEqual(10, strlen($code));
    }

    public function testValidateCodeRejectsReservedCodes(): void
    {
        $reserved = ['api', 'admin', 'static', 'login', 'dashboard', 'health'];

        foreach ($reserved as $code) {
            $this->assertFalse(ShortCodeGenerator::isValidCustom($code));
        }
    }

    public function testValidateCodeAcceptsValidCodes(): void
    {
        $validCodes = ['mylink', 'test-123', 'abc-def'];

        foreach ($validCodes as $code) {
            $this->assertTrue(ShortCodeGenerator::isValidCustom($code));
        }
    }

    public function testValidateCodeRejectsTooShort(): void
    {
        $this->assertFalse(ShortCodeGenerator::isValidCustom('ab'));
    }

    public function testValidateCodeRejectsTooLong(): void
    {
        $code = str_repeat('a', 65);
        $this->assertFalse(ShortCodeGenerator::isValidCustom($code));
    }

    public function testValidateCodeRejectsSpecialCharacters(): void
    {
        $invalidCodes = ['my@link', 'test!', 'code#123', 'my link'];

        foreach ($invalidCodes as $code) {
            $this->assertFalse(ShortCodeGenerator::isValidCustom($code));
        }
    }
}
