<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\ContentNegotiation\Factory;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\ServiceManager\ServiceManager;
use ZF\ContentNegotiation\ContentNegotiationOptions;
use ZF\ContentNegotiation\Factory\AcceptListenerFactory;

class AcceptListenerFactoryTest extends TestCase
{
    public function testCreateServiceShouldReturnAcceptListenerInstance()
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService(
            'ZF\ContentNegotiation\ContentNegotiationOptions',
            new ContentNegotiationOptions()
        );

        $factory = new AcceptListenerFactory();

        $service = $factory->createService($serviceManager);

        $this->assertInstanceOf('ZF\ContentNegotiation\AcceptListener', $service);
    }
}
