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
        $serviceManager->setService('config', $config);

        $factory = new ContentNegotiationOptionsFactory();

        $service = $factory($serviceManager, 'ContentNegotiationOptions');

        $this->assertInstanceOf('ZF\ContentNegotiation\ContentNegotiationOptions', $service);
    }

    public function testCreateServiceShouldReturnContentNegotiationOptionsInstanceWithOptions()
    {
        $config = [
            'zf-content-negotiation' => [
                'accept_whitelist' => [],
            ],
        ];

        $serviceManager = new ServiceManager();
        $serviceManager->setService('config', $config);

        $factory = new ContentNegotiationOptionsFactory();

        $service = $factory($serviceManager, 'ContentNegotiationOptions');

        $this->assertNotEmpty($service->toArray());
    }

    public function testCreateServiceWithoutConfigShouldReturnContentNegotiationOptionsInstance()
    {
        $serviceManager = new ServiceManager();

        $factory = new ContentNegotiationOptionsFactory();

        $service = $factory($serviceManager, 'ContentNegotiationOptions');

        $this->assertNotEmpty($service->toArray());
    }
}
