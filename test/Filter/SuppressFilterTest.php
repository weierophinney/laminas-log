<?php

/**
 * @see       https://github.com/laminas/laminas-log for the canonical source repository
 * @copyright https://github.com/laminas/laminas-log/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-log/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Log\Filter;

use Laminas\Log\Filter\SuppressFilter;
use PHPUnit\Framework\TestCase;

class SuppressFilterTest extends TestCase
{
    protected function setUp(): void
    {
        $this->filter = new SuppressFilter();
    }

    public function testSuppressIsInitiallyOff(): void
    {
        $this->assertTrue($this->filter->filter([]));
    }

    public function testSuppressByConstructorBoolean(): void
    {
        $this->filter = new SuppressFilter(true);
        $this->assertFalse($this->filter->filter([]));
        $this->assertFalse($this->filter->filter([]));
    }

    public function testSuppressByConstructorArray(): void
    {
        $this->filter = new SuppressFilter(['suppress' => true]);
        $this->assertFalse($this->filter->filter([]));
        $this->assertFalse($this->filter->filter([]));
    }

    public function testConstructorThrowsOnInvalidSuppressValue(): void
    {
        $this->expectException('Laminas\Log\Exception\InvalidArgumentException');
        $this->expectExceptionMessage('Suppress must be a boolean');
        new SuppressFilter('foo');
    }

    public function testSuppressOn(): void
    {
        $this->filter->suppress(true);
        $this->assertFalse($this->filter->filter([]));
        $this->assertFalse($this->filter->filter([]));
    }

    public function testSuppressOff(): void
    {
        $this->filter->suppress(false);
        $this->assertTrue($this->filter->filter([]));
        $this->assertTrue($this->filter->filter([]));
    }

    public function testSuppressCanBeReset(): void
    {
        $this->filter->suppress(true);
        $this->assertFalse($this->filter->filter([]));
        $this->filter->suppress(false);
        $this->assertTrue($this->filter->filter([]));
        $this->filter->suppress(true);
        $this->assertFalse($this->filter->filter([]));
    }
}
