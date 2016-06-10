<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\ContentNegotiation;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\EventManager\EventManager;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Router\RouteMatch;
use ZF\ContentNegotiation\ContentTypeFilterListener;

class ContentTypeFilterListenerTest extends TestCase
{
    public function setUp()
    {
        $this->listener   = new ContentTypeFilterListener();
        $this->event      = new MvcEvent();
        $this->event->setTarget(new TestAsset\ContentTypeController());
        $this->event->setRequest(new Request());
        $this->event->setRouteMatch(new RouteMatch([
            'controller' => __NAMESPACE__ . '\TestAsset\ContentTypeController',
        ]));
    }

    public function testListenerDoesNothingIfNoConfigurationExistsForController()
    {
        $this->assertNull($this->listener->onRoute($this->event));
    }

    public function testListenerDoesNothingIfRequestContentTypeIsInControllerWhitelist()
    {
        $contentType = 'application/vnd.zf.v1.foo+json';
        $this->listener->setConfig([
            'ZFTest\ContentNegotiation\TestAsset\ContentTypeController' => [
                $contentType,
            ],
        ]);
        $this->event->getRequest()->getHeaders()->addHeaderLine('content-type', $contentType);
        $this->assertNull($this->listener->onRoute($this->event));
    }

    public function testListenerReturnsApiProblemResponseIfRequestContentTypeIsNotInControllerWhitelist()
    {
        $contentType = 'application/vnd.zf.v1.foo+json';
        $this->listener->setConfig([
            'ZFTest\ContentNegotiation\TestAsset\ContentTypeController' => [
                'application/xml',
            ],
        ]);
        $request = $this->event->getRequest();
        $request->getHeaders()->addHeaderLine('content-type', $contentType);
        $request->setContent('<?xml version="1.0"?><foo><bar>baz</bar></foo>');

        $response = $this->listener->onRoute($this->event);
        $this->assertInstanceOf('ZF\ApiProblem\ApiProblemResponse', $response);
        $this->assertContains('Invalid content-type', $response->getApiProblem()->detail);
    }
    

    /**
     * @group 66
     */
    public function testCastsObjectBodyContentToStringBeforeWorkingWithIt()
    {
        $contentType = 'application/vnd.zf.v1.foo+json';
        $this->listener->setConfig([
            'ZFTest\ContentNegotiation\TestAsset\ContentTypeController' => [
                $contentType,
            ],
        ]);
        $request = $this->event->getRequest();

        $request->getHeaders()->addHeaderLine('content-type', $contentType);
        $request->setContent(new TestAsset\BodyContent());

        $this->assertNull($this->listener->onRoute($this->event));
    }
}
