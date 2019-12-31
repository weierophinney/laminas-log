<?php

/**
 * @see       https://github.com/laminas/laminas-log for the canonical source repository
 * @copyright https://github.com/laminas/laminas-log/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-log/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Log;

use Laminas\Log\Filter\Mock as MockFilter;
use Laminas\Log\Logger;
use Laminas\Log\Writer\Mock as MockWriter;
use Laminas\Stdlib\SplPriorityQueue;
use Laminas\Validator\Digits as DigitsFilter;

/**
 * @category   Laminas
 * @package    Laminas_Log
 * @subpackage UnitTests
 * @group      Laminas_Log
 */
class LoggerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->logger = new Logger;
    }

    public function testUsesWriterPluginManagerByDefault()
    {
        $this->assertInstanceOf('Laminas\Log\WriterPluginManager', $this->logger->getWriterPluginManager());
    }

    public function testPassingValidStringClassToSetPluginManager()
    {
        $this->logger->setWriterPluginManager('Laminas\Log\WriterPluginManager');
        $this->assertInstanceOf('Laminas\Log\WriterPluginManager', $this->logger->getWriterPluginManager());
    }

    public static function provideInvalidClasses()
    {
        return array(
            array('stdClass'),
            array(new \stdClass()),
        );
    }

    /**
     * @dataProvider provideInvalidClasses
     */
    public function testPassingInvalidArgumentToSetPluginManagerRaisesException($plugins)
    {
        $this->setExpectedException('Laminas\Log\Exception\InvalidArgumentException');
        $this->logger->setWriterPluginManager($plugins);
    }

    public function testPassingShortNameToPluginReturnsWriterByThatName()
    {
        $writer = $this->logger->writerPlugin('mock');
        $this->assertInstanceOf('Laminas\Log\Writer\Mock', $writer);
    }

    public function testPassWriterAsString()
    {
        $this->logger->addWriter('mock');
        $writers = $this->logger->getWriters();
        $this->assertInstanceOf('Laminas\Stdlib\SplPriorityQueue', $writers);
    }

    /**
     * @dataProvider provideInvalidClasses
     */
    public function testPassingInvalidArgumentToAddWriterRaisesException($writer)
    {
        $this->setExpectedException('Laminas\Log\Exception\InvalidArgumentException', 'must implement');
        $this->logger->addWriter($writer);
    }

    public function testEmptyWriter()
    {
        $this->setExpectedException('Laminas\Log\Exception\RuntimeException', 'No log writer specified');
        $this->logger->log(Logger::INFO, 'test');
    }

    public function testSetWriters()
    {
        $writer1 = $this->logger->writerPlugin('mock');
        $writer2 = $this->logger->writerPlugin('null');
        $writers = new SplPriorityQueue();
        $writers->insert($writer1, 1);
        $writers->insert($writer2, 2);
        $this->logger->setWriters($writers);

        $writers = $this->logger->getWriters();
        $this->assertInstanceOf('Laminas\Stdlib\SplPriorityQueue', $writers);
        $writer = $writers->extract();
        $this->assertTrue($writer instanceof \Laminas\Log\Writer\Null);
        $writer = $writers->extract();
        $this->assertTrue($writer instanceof \Laminas\Log\Writer\Mock);
    }

    public function testAddWriterWithPriority()
    {
        $writer1 = $this->logger->writerPlugin('mock');
        $this->logger->addWriter($writer1,1);
        $writer2 = $this->logger->writerPlugin('null');
        $this->logger->addWriter($writer2,2);
        $writers = $this->logger->getWriters();

        $this->assertInstanceOf('Laminas\Stdlib\SplPriorityQueue', $writers);
        $writer = $writers->extract();
        $this->assertTrue($writer instanceof \Laminas\Log\Writer\Null);
        $writer = $writers->extract();
        $this->assertTrue($writer instanceof \Laminas\Log\Writer\Mock);

    }

    public function testAddWithSamePriority()
    {
        $writer1 = $this->logger->writerPlugin('mock');
        $this->logger->addWriter($writer1,1);
        $writer2 = $this->logger->writerPlugin('null');
        $this->logger->addWriter($writer2,1);
        $writers = $this->logger->getWriters();

        $this->assertInstanceOf('Laminas\Stdlib\SplPriorityQueue', $writers);
        $writer = $writers->extract();
        $this->assertTrue($writer instanceof \Laminas\Log\Writer\Mock);
        $writer = $writers->extract();
        $this->assertTrue($writer instanceof \Laminas\Log\Writer\Null);
    }

    public function testLogging()
    {
        $writer = new MockWriter;
        $this->logger->addWriter($writer);
        $this->logger->log(Logger::INFO, 'tottakai');

        $this->assertEquals(count($writer->events), 1);
        $this->assertContains('tottakai', $writer->events[0]['message']);
    }

    public function testLoggingArray()
    {
        $writer = new MockWriter;
        $this->logger->addWriter($writer);
        $this->logger->log(Logger::INFO, array('test'));

        $this->assertEquals(count($writer->events), 1);
        $this->assertContains('test', $writer->events[0]['message']);
    }

    public function testAddFilter()
    {
        $writer = new MockWriter;
        $filter = new MockFilter;
        $writer->addFilter($filter);
        $this->logger->addWriter($writer);
        $this->logger->log(Logger::INFO, array('test'));

        $this->assertEquals(count($filter->events), 1);
        $this->assertContains('test', $filter->events[0]['message']);
    }

    public function testAddFilterByName()
    {
        $writer = new MockWriter;
        $writer->addFilter('mock');
        $this->logger->addWriter($writer);
        $this->logger->log(Logger::INFO, array('test'));

        $this->assertEquals(count($writer->events), 1);
        $this->assertContains('test', $writer->events[0]['message']);
    }

    /**
     * provideTestFilters
     */
    public function provideTestFilters()
    {
        return array(
            array('priority', array('priority' => Logger::INFO)),
            array('regex', array( 'regex' => '/[0-9]+/' )),
            array('validator', array('validator' => new DigitsFilter)),
        );
    }

    /**
     * @dataProvider provideTestFilters
     */
    public function testAddFilterByNameWithParams($filter, $options)
    {
        $writer = new MockWriter;
        $writer->addFilter($filter, $options);
        $this->logger->addWriter($writer);

        $this->logger->log(Logger::INFO, '123');
        $this->assertEquals(count($writer->events), 1);
        $this->assertContains('123', $writer->events[0]['message']);
    }

    public static function provideAttributes()
    {
        return array(
            array(array()),
            array(array('user' => 'foo', 'ip' => '127.0.0.1')),
            array(new \ArrayObject(array('id' => 42))),
        );
    }

    /**
     * @dataProvider provideAttributes
     */
    public function testLoggingCustomAttributesForUserContext($extra)
    {
        $writer = new MockWriter;
        $this->logger->addWriter($writer);
        $this->logger->log(Logger::ERR, 'tottakai', $extra);

        $this->assertEquals(count($writer->events), 1);
        $this->assertInternalType('array', $writer->events[0]['extra']);
        $this->assertEquals(count($writer->events[0]['extra']), count($extra));
    }

    public static function provideInvalidArguments()
    {
        return array(
            array(new \stdClass(), array('valid')),
            array('valid', null),
            array('valid', true),
            array('valid', 10),
            array('valid', 'invalid'),
            array('valid', new \stdClass()),
        );
    }

    /**
     * @dataProvider provideInvalidArguments
     */
    public function testPassingInvalidArgumentToLogRaisesException($message, $extra)
    {
        $this->setExpectedException('Laminas\Log\Exception\InvalidArgumentException');
        $this->logger->log(Logger::ERR, $message, $extra);
    }

    public function testRegisterErrorHandler()
    {
        $writer = new MockWriter;
        $this->logger->addWriter($writer);

        $this->assertTrue(Logger::registerErrorHandler($this->logger));
        // check for single error handler instance
        $this->assertFalse(Logger::registerErrorHandler($this->logger));
        // generate a warning
        echo $test;
        Logger::unregisterErrorHandler();
        $this->assertEquals($writer->events[0]['message'], 'Undefined variable: test');
    }
}
