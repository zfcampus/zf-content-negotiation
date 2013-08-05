<?php

namespace ZFTest\ContentNegotiation;

use PHPUnit_Framework_TestCase as TestCase;
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
}
