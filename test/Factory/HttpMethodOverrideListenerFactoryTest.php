<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\ContentNegotiation\Factory;

use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
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

        /** @var ContainerInterface|ObjectProphecy $container */
        $container = $this->prophesize(ContainerInterface::class);
        $container->get(ContentNegotiationOptions::class)->willReturn($options);

        $factory = new HttpMethodOverrideListenerFactory();
        $service = $factory($container->reveal(), HttpMethodOverrideListener::class);

        $this->assertInstanceOf(HttpMethodOverrideListener::class, $service);
    }
}
