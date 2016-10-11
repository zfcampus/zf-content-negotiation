<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\ContentNegotiation\Factory;

use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;
use ZF\ContentNegotiation\ContentNegotiationOptions;
use ZF\ContentNegotiation\Factory\HttpMethodOverrideListenerFactory;
use ZF\ContentNegotiation\HttpMethodOverrideListener;

class HttpMethodOverrideListenerFactoryTest extends TestCase
{
    public function testCreateServiceShouldReturnContentTypeFilterListenerInstance()
    {
        /** @var ContentNegotiationOptions|ObjectProphecy $options */
        $options = $this->prophesize(ContentNegotiationOptions::class);
        $options->getHttpOverrideMethods()->willReturn([]);

        /** @var ServiceManager|ObjectProphecy $container */
        $container = $this->prophesize(ServiceManager::class);
        $container->willImplement(ServiceLocatorInterface::class);
        $container->get(ContentNegotiationOptions::class)->willReturn($options);

        $factory = new HttpMethodOverrideListenerFactory();
        $service = $factory($container->reveal(), HttpMethodOverrideListener::class);

        $this->assertInstanceOf(HttpMethodOverrideListener::class, $service);
    }
}
