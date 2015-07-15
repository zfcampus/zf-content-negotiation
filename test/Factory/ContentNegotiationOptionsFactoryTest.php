<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\ContentNegotiation\Factory;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\ServiceManager\ServiceManager;
use ZF\ContentNegotiation\Factory\ContentNegotiationOptionsFactory;

class ContentNegotiationOptionsFactoryTest extends TestCase
{
    public function testCreateServiceShouldReturnContentNegotiationOptionsInstance()
    {
        $config = [
            'zf-content-negotiation' => [
                'accept_whitelist' => [],
            ],
        ];

        $serviceManager = new ServiceManager();
        $serviceManager->setService('Config', $config);

        $factory = new ContentNegotiationOptionsFactory();

        $service = $factory->createService($serviceManager);

        $this->assertInstanceOf('ZF\ContentNegotiation\ContentNegotiationOptions', $service);
    }
}
