<?php

use PHPUnit\Framework\TestCase;

class UrlValidatorTest extends TestCase
{
    public function testValidateAcceptsHttpUrls(): void
    {
        $urls = [
            'http://example.com',
            'http://example.com/path',
            'http://example.com/path?query=1',
        ];

        foreach ($urls as $url) {
            $this->assertTrue(UrlValidator::validate($url), "Failed for URL: $url");
        }
    }

    public function testValidateAcceptsHttpsUrls(): void
    {
        $urls = [
            'https://example.com',
            'https://example.com/path',
            'https://example.com/path?query=1#fragment',
        ];

        foreach ($urls as $url) {
            $this->assertTrue(UrlValidator::validate($url), "Failed for URL: $url");
        }
    }

    public function testValidateRejectsInvalidSchemes(): void
    {
        $urls = [
            'javascript:alert("xss")',
            'data:text/html,<script>alert("xss")</script>',
            'ftp://example.com',
            'file:///etc/passwd',
            'mailto:test@example.com',
        ];

        foreach ($urls as $url) {
            $this->assertFalse(UrlValidator::validate($url), "Should reject: $url");
        }
    }

    public function testValidateRejectsNoScheme(): void
    {
        $this->assertFalse(UrlValidator::validate('example.com'));
    }

    public function testValidateRejectsInvalidUrls(): void
    {
        $urls = [
            'not a url',
            'ht://invalid',
            '',
        ];

        foreach ($urls as $url) {
            $this->assertFalse(UrlValidator::validate($url));
        }
    }

    public function testNormalizeConvertsToLowercase(): void
    {
        $url = 'HTTPS://EXAMPLE.COM/Path';
        $normalized = UrlValidator::normalize($url);

        $this->assertStringStartsWith('https://', $normalized);
        $this->assertStringContainsString('example.com', $normalized);
    }

    public function testNormalizeStripsDefaultPorts(): void
    {
        $urls = [
            'http://example.com:80' => 'http://example.com',
            'https://example.com:443' => 'https://example.com',
        ];

        foreach ($urls as $input => $expected) {
            $this->assertEquals($expected, UrlValidator::normalize($input));
        }
    }

    public function testNormalizePreservesNonDefaultPorts(): void
    {
        $url = 'https://example.com:8443';
        $normalized = UrlValidator::normalize($url);

        $this->assertStringContainsString(':8443', $normalized);
    }
}
