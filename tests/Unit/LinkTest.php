<?php

use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    protected function setUp(): void
    {
        Link::db()->exec('DELETE FROM links');
    }

    public function testCreateLink(): void
    {
        $data = [
            'short_code' => 'test123',
            'original_url' => 'https://example.com',
        ];

        $link = Link::create($data);

        $this->assertNotNull($link);
        $this->assertEquals('test123', $link['short_code']);
        $this->assertEquals('https://example.com', $link['original_url']);
    }

    public function testFindByCode(): void
    {
        $data = [
            'short_code' => 'find123',
            'original_url' => 'https://github.com',
        ];
        Link::create($data);

        $link = Link::findByCode('find123');

        $this->assertNotNull($link);
        $this->assertEquals('find123', $link['short_code']);
        $this->assertEquals('https://github.com', $link['original_url']);
    }

    public function testFindByCodeNotFound(): void
    {
        $link = Link::findByCode('nonexistent');
        $this->assertNull($link);
    }

    public function testIncrementClicks(): void
    {
        $data = [
            'short_code' => 'click1',
            'original_url' => 'https://example.com',
        ];
        Link::create($data);

        Link::incrementClicks('click1');
        Link::incrementClicks('click1');

        $link = Link::findByCode('click1');
        $this->assertEquals(2, $link['clicks']);
    }

    public function testSoftDelete(): void
    {
        $data = [
            'short_code' => 'delete1',
            'original_url' => 'https://example.com',
        ];
        Link::create($data);

        Link::deleteByCode('delete1');

        $link = Link::findByCode('delete1');
        $this->assertEquals(0, $link['is_active']);
    }

    public function testRestore(): void
    {
        $data = [
            'short_code' => 'restore1',
            'original_url' => 'https://example.com',
        ];
        Link::create($data);
        Link::deleteByCode('restore1');

        Link::restoreByCode('restore1');

        $link = Link::findByCode('restore1');
        $this->assertEquals(1, $link['is_active']);
    }

    public function testFindPaginated(): void
    {
        for ($i = 0; $i < 5; $i++) {
            Link::create([
                'short_code' => 'page' . $i,
                'original_url' => 'https://example.com/page' . $i,
            ]);
        }

        $result = Link::findPaginated(limit: 2, offset: 0);

        $this->assertCount(2, $result['links']);
        $this->assertEquals(5, $result['total']);
        $this->assertTrue($result['has_more']);
    }

    public function testPermanentDelete(): void
    {
        $data = [
            'short_code' => 'perm1',
            'original_url' => 'https://example.com',
        ];
        Link::create($data);
        Link::deleteByCode('perm1');

        $result = Link::permanentlyDeleteByCode('perm1');

        $this->assertTrue($result);
        $this->assertNull(Link::findByCode('perm1'));
    }
}
