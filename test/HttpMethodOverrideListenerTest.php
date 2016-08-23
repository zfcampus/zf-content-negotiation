<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\ContentNegotiation;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Http\Request as HttpRequest;
use Zend\Mvc\MvcEvent;
use ZF\ContentNegotiation\HttpMethodOverrideListener;

class HttpMethodOverrideListenerTest extends TestCase
{
    use RouteMatchFactoryTrait;

    /**
     * @var HttpMethodOverrideListener
     */
    protected $listener;

    /**
     * Set up test
     */
    public function setUp()
    {
        $this->listener = new HttpMethodOverrideListener();
    }

    /**
     * @return array
     */
    public function httpMethods()
    {
        return [
            'head' => [HttpRequest::METHOD_HEAD],
            'post' => [HttpRequest::METHOD_POST],
            'put' => [HttpRequest::METHOD_PUT],
            'delete' => [HttpRequest::METHOD_DELETE],
            'patch' => [HttpRequest::METHOD_PATCH],
        ];
    }

    /**
     * @dataProvider httpMethods
     */
    public function testHttpMethodOverrideListener($method)
    {
        $listener = $this->listener;

        $request = new HttpRequest();
        $request->setMethod('GET');
        $request->getHeaders()->addHeaderLine('X-HTTP-Method-Override', $method);

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setRouteMatch($this->createRouteMatch([]));

        $result = $listener($event);
        $this->assertEquals($method, $request->getMethod());

    }

    /**
     *
     */
    public function testHttpMethodOverrideListenerReturnsProblemResponse()
    {
        $listener = $this->listener;

        $request = new HttpRequest();
        $request->setMethod('GET');
        $request->getHeaders()->addHeaderLine('X-HTTP-Method-Override', 'TEST');

        $event = new MvcEvent();
        $event->setRequest($request);

        $result = $listener($event);
        $this->assertInstanceOf('ZF\ApiProblem\ApiProblemResponse', $result);
        $problem = $result->getApiProblem();
        $this->assertEquals(400, $problem->status);
        $this->assertContains('unrecognized method in X-HTTP-Method-Ovverride header', $problem->detail);
    }
}
