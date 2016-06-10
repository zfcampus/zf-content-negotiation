<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\ContentNegotiation;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Http\Request;
use Zend\Mvc\Controller\PluginManager as ControllerPluginManager;
use Zend\Mvc\MvcEvent;
use Zend\Router\RouteMatch;
use Zend\ServiceManager\ServiceManager;
use ZF\ContentNegotiation\AcceptListener;

class AcceptListenerTest extends TestCase
{
    public function setUp()
    {
        $plugins  = new ControllerPluginManager(new ServiceManager());
        $selector = $plugins->get('AcceptableViewModelSelector');

        $this->listener   = new AcceptListener($selector, [
            'controllers' => [
                'ZFTest\ContentNegotiation\TestAsset\ContentTypeController' => 'Json',
            ],
            'selectors' => [
                'Json' => [
                    'Zend\View\Model\JsonModel' => [
                        'application/json',
                        'application/*+json',
                    ],
                ],
            ],
        ]);
        $this->event      = new MvcEvent();
        $this->controller = new TestAsset\ContentTypeController();
        $this->event->setTarget($this->controller);
        $this->event->setRequest(new Request);
        $this->event->setRouteMatch(new RouteMatch([
            'controller' => __NAMESPACE__ . '\TestAsset\ContentTypeController',
        ]));

        $this->controller->setEvent($this->event);
        $this->controller->setRequest($this->event->getRequest());
        $this->controller->setPluginManager($plugins);
    }

    public function testInabilityToResolveViewModelReturnsApiProblemResponse()
    {
        $listener = $this->listener;
        $this->event->setResult(['foo' => 'bar']);

        $response = $listener($this->event);
        $this->assertInstanceOf('ZF\ApiProblem\ApiProblemResponse', $response);
        $this->assertEquals(406, $response->getApiProblem()->status);
        $this->assertContains('Unable to resolve', $response->getApiProblem()->detail);
    }

    public function testReturnADefaultViewModelIfNoCriteriaSpecifiedForAController()
    {
        $selector = $this->controller->plugin('AcceptableViewModelSelector');
        $listener = new AcceptListener($selector, []);
        $this->event->setResult(['foo' => 'bar']);

        $listener($this->event);
        $result = $this->event->getResult();
        $this->assertInstanceOf('Zend\View\Model\ModelInterface', $result);
    }

    /**
     * @group 22
     */
    public function testShouldExitEarlyIfNonHttpRequestPresentInEvent()
    {
        $request = $this->getMock('Zend\Stdlib\RequestInterface');
        $this->event->setRequest($request);

        $listener = $this->listener;
        $this->event->setResult(['foo' => 'bar']);

        $this->assertNull($listener($this->event));
    }
}
