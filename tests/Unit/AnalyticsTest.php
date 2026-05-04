<?php

use PHPUnit\Framework\TestCase;

class AnalyticsTest extends TestCase
{
    protected function setUp(): void
    {
        // Clear analytics table before each test
        Analytics::db()->exec('DELETE FROM analytics');
        Link::db()->exec('DELETE FROM links');
    }

    public function testRecordClickStoresData(): void
    {
        Link::create(['short_code' => 'test1', 'original_url' => 'https://example.com']);

        $result = Analytics::recordClick('test1', 'Mozilla/5.0', 'https://referrer.com', 'hash123');

        $this->assertTrue($result);

        $analytics = Analytics::getAnalytics('test1');
        $this->assertCount(1, $analytics);
        $this->assertEquals('test1', $analytics[0]['short_code']);
    }

    public function testGetAnalyticsPaginated(): void
    {
        Link::create(['short_code' => 'test2', 'original_url' => 'https://example.com']);

        for ($i = 0; $i < 5; $i++) {
            Analytics::recordClick('test2', "Agent$i", null, "hash$i");
        }

        $result = Analytics::getAnalytics('test2', limit: 2, offset: 0);

        $this->assertCount(2, $result);
    }

    public function testGetAnalyticsCountReturnsTotal(): void
    {
        Link::create(['short_code' => 'test3', 'original_url' => 'https://example.com']);

        for ($i = 0; $i < 3; $i++) {
            Analytics::recordClick('test3', null, null, null);
        }

        $count = Analytics::getAnalyticsCount('test3');

        $this->assertEquals(3, $count);
    }

    public function testGetSummarySummary(): void
    {
        Link::create(['short_code' => 'test4', 'original_url' => 'https://example.com']);

        Analytics::recordClick('test4', 'Agent1', null, 'hash1');
        Analytics::recordClick('test4', 'Agent1', null, 'hash2');
        Analytics::recordClick('test4', 'Agent2', null, 'hash1');

        $summary = Analytics::getSummary('test4');

        $this->assertEquals(3, $summary['total_clicks']);
        $this->assertEquals(2, $summary['unique_ips']);
        $this->assertEquals(2, $summary['unique_agents']);
    }

    public function testDeleteByCodeRemovesAnalytics(): void
    {
        Link::create(['short_code' => 'test5', 'original_url' => 'https://example.com']);
        Analytics::recordClick('test5', null, null, null);

        Analytics::deleteByCode('test5');

        $count = Analytics::getAnalyticsCount('test5');
        $this->assertEquals(0, $count);
    }

    public function testRecordClickWithNullValues(): void
    {
        Link::create(['short_code' => 'test6', 'original_url' => 'https://example.com']);

        $result = Analytics::recordClick('test6', null, null, null);

        $this->assertTrue($result);

        $analytics = Analytics::getAnalytics('test6');
        $this->assertCount(1, $analytics);
        $this->assertNull($analytics[0]['user_agent']);
        $this->assertNull($analytics[0]['referrer']);
        $this->assertNull($analytics[0]['ip_hash']);
    }
}
