<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\ContentNegotiation;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\EventManager\SharedEventManager;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use ZF\ContentNegotiation\ContentTypeFilterListener;

class ContentTypeFilterListenerTest extends TestCase
{
    public function setUp()
    {
        $this->listener   = new ContentTypeFilterListener();
        $this->event      = new MvcEvent();
        $this->event->setTarget(new TestAsset\ContentTypeController());
        $this->event->setRequest(new Request());
        $this->event->setRouteMatch(new RouteMatch(array(
            'controller' => __NAMESPACE__ . '\TestAsset\ContentTypeController',
        )));
    }

    public function testListenerDoesNothingIfNoConfigurationExistsForController()
    {
        $this->assertNull($this->listener->onDispatch($this->event));
    }

    public function testListenerDoesNothingIfRequestContentTypeIsInControllerWhitelist()
    {
        $contentType = 'application/vnd.zf.v1.foo+json';
        $this->listener->setConfig(array(
            'ZFTest\ContentNegotiation\TestAsset\ContentTypeController' => array(
                $contentType,
            ),
        ));
        $this->event->getRequest()->getHeaders()->addHeaderLine('content-type', $contentType);
        $this->assertNull($this->listener->onDispatch($this->event));
    }

    public function testListenerReturnsApiProblemResponseIfRequestContentTypeIsNotInControllerWhitelist()
    {
        $contentType = 'application/vnd.zf.v1.foo+json';
        $this->listener->setConfig(array(
            'ZFTest\ContentNegotiation\TestAsset\ContentTypeController' => array(
                'application/xml',
            ),
        ));
        $request = $this->event->getRequest();
        $request->getHeaders()->addHeaderLine('content-type', $contentType);
        $request->setContent('<?xml version="1.0"?><foo><bar>baz</bar></foo>');

        $response = $this->listener->onDispatch($this->event);
        $this->assertInstanceOf('ZF\ApiProblem\ApiProblemResponse', $response);
        $this->assertContains('Invalid content-type', $response->getApiProblem()->detail);
    }

    public function testAttachSharedAttachesToDispatchEventAtHighPriority()
    {
        $events = new SharedEventManager();
        $this->listener->attachShared($events);
        $listeners = $events->getListeners('Zend\Stdlib\DispatchableInterface', 'dispatch');
        $this->assertEquals(1, count($listeners));
        $this->assertTrue($listeners->hasPriority(100));
        $callback = $listeners->getIterator()->current()->getCallback();
        $this->assertEquals(array($this->listener, 'onDispatch'), $callback);
    }
}
