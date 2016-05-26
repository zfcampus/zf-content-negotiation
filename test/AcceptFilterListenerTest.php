<?php
/**
 * @link      http://github.com/zfcampus/zf-content-negotiation for the canonical source repository
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 */

namespace ZFTest\ContentNegotiation;

use PHPUnit_Framework_TestCase as TestCase;
use ReflectionMethod;
use Zend\Http\Headers;
use ZF\ContentNegotiation\AcceptFilterListener;

class AcceptFilterListenerTest extends TestCase
{
    public function setUp()
    {
        $this->listener = new AcceptFilterListener();
    }

    /**
     * @group 58
     */
    public function testMissingAcceptHeaderIndicatesValidMediaType()
    {
        $headers = $this->prophesize(Headers::class);
        $headers->has('accept')->willReturn(false);

        $r = new ReflectionMethod($this->listener, 'validateMediaType');
        $r->setAccessible(true);

        $this->assertTrue($r->invoke($this->listener, 'application/json', $headers->reveal()));
    }
}
