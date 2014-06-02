<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\ContentNegotiation;

use PHPUnit_Framework_TestCase as TestCase;
use ReflectionObject;
use ZF\ContentNegotiation\Request;

class RequestTest extends TestCase
{
    public function setUp()
    {
        $this->request = new Request();
    }

    public function testIsAnHttpRequest()
    {
        $this->assertInstanceOf('Zend\Http\Request', $this->request);
    }

    public function testIsAPhpEnvironmentHttpRequest()
    {
        $this->assertInstanceOf('Zend\Http\PhpEnvironment\Request', $this->request);
    }

    public function testDefinesAGetContentAsStreamMethod()
    {
        $this->assertTrue(method_exists($this->request, 'getContentAsStream'));
    }

    public function testDefaultContentStreamIsPhpInputStream()
    {
        $this->assertAttributeEquals('php://input', 'contentStream', $this->request);
    }

    public function testCanSetStreamUriForContent()
    {
        $expected = 'file://' . realpath(__FILE__);
        $this->request->setContentStream($expected);
        $this->assertAttributeEquals($expected, 'contentStream', $this->request);
    }

    public function testGetContentAsStreamReturnsResource()
    {
        $this->request->setContentStream('file://' . realpath(__FILE__));
        $stream = $this->request->getContentAsStream();
        $this->assertInternalType('resource', $stream);
    }

    public function testReturnsPhpTemporaryStreamIfContentHasAlreadyBeenRetrieved()
    {
        $r = new ReflectionObject($this->request);
        $p = $r->getProperty('content');
        $p->setAccessible(true);
        $p->setValue($this->request, 'bam!');

        $stream = $this->request->getContentAsStream();
        $this->assertEquals('bam!', stream_get_contents($stream));
    }
}
