<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\ContentNegotiation;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Http\Request as HttpRequest;
use Zend\Mvc\MvcEvent;
use ZF\ApiProblem\ApiProblemResponse;
use ZF\ContentNegotiation\HttpMethodOverrideListener;

class HttpMethodOverrideListenerTest extends TestCase
{
    use RouteMatchFactoryTrait;

    /**
     * @var HttpMethodOverrideListener
     */
    protected $listener;

    /**
     * @var array
     */
    protected $httpMethodOverride = [
        HttpRequest::METHOD_GET => [
            HttpRequest::METHOD_HEAD,
            HttpRequest::METHOD_POST,
            HttpRequest::METHOD_PUT,
            HttpRequest::METHOD_DELETE,
            HttpRequest::METHOD_PATCH,
        ],
        HttpRequest::METHOD_POST => [
        ],
    ];

    /**
     * Set up test
     */
    public function setUp()
    {
        $this->listener = new HttpMethodOverrideListener($this->httpMethodOverride);
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

        $result = $listener->onRoute($event);
        $this->assertEquals($method, $request->getMethod());
    }

    /**
     * @dataProvider httpMethods
     */
    public function testHttpMethodOverrideListenerReturnsProblemResponseForMethodNotInConfig($method)
    {
        $listener = $this->listener;

        $request = new HttpRequest();
        $request->setMethod('PATCH');
        $request->getHeaders()->addHeaderLine('X-HTTP-Method-Override', $method);

        $event = new MvcEvent();
        $event->setRequest($request);

        $result = $listener->onRoute($event);
        $this->assertInstanceOf(ApiProblemResponse::class, $result);
        $problem = $result->getApiProblem();
        $this->assertEquals(400, $problem->status);
        $this->assertContains(
            'Overriding PATCH method with X-HTTP-Method-Override header is not allowed',
            $problem->detail
        );
    }

    /**
     * @dataProvider httpMethods
     */
    public function testHttpMethodOverrideListenerReturnsProblemResponseForIllegalOverrideValue($method)
    {
        $listener = $this->listener;

        $request = new HttpRequest();
        $request->setMethod('POST');
        $request->getHeaders()->addHeaderLine('X-HTTP-Method-Override', $method);

        $event = new MvcEvent();
        $event->setRequest($request);

        $result = $listener->onRoute($event);
        $this->assertInstanceOf(ApiProblemResponse::class, $result);
        $problem = $result->getApiProblem();
        $this->assertEquals(400, $problem->status);
        $this->assertContains(
            sprintf('Illegal override method %s in X-HTTP-Method-Override header', $method),
            $problem->detail
        );
    }
}
