<?php

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
        $this->event->setRequest(new Request);
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

    public function testListenerRaisesExceptionIfRequestContentTypeIsNotInControllerWhitelist()
    {
        $contentType = 'application/vnd.zf.v1.foo+json';
        $this->listener->setConfig(array(
            'ZFTest\ContentNegotiation\TestAsset\ContentTypeController' => array(
                'application/json',
            ),
        ));
        $this->event->getRequest()->getHeaders()->addHeaderLine('content-type', $contentType);

        $this->setExpectedException('ZF\ApiProblem\Exception\DomainException', 'Invalid content-type');
        $this->listener->onDispatch($this->event);
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
