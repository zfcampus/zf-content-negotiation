<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\ContentNegotiation\Factory;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\ServiceManager\ServiceManager;
use ZF\ContentNegotiation\ContentNegotiationOptions;
use ZF\ContentNegotiation\Factory\HttpMethodOverrideListenerFactory;
use ZF\ContentNegotiation\HttpMethodOverrideListener;

class HttpMethodOverrideListenerFactoryTest extends TestCase
{
    public function testCreateServiceShouldReturnContentTypeFilterListenerInstance()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            ContentNegotiationOptions::class,
            new ContentNegotiationOptions()
        );

        $factory = new HttpMethodOverrideListenerFactory();

        $service = $factory($serviceManager, 'HttpMethodOverrideListener');

        $this->assertInstanceOf(HttpMethodOverrideListener::class, $service);
    }
}
