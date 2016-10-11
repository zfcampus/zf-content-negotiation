<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation\Factory;

use Interop\Container\ContainerInterface;
use ZF\ContentNegotiation\ContentNegotiationOptions;
use ZF\ContentNegotiation\HttpMethodOverrideListener;

class HttpMethodOverrideListenerFactory
{
    /**
     * @param  ContainerInterface $container
     * @return HttpMethodOverrideListener
     */
    public function __invoke(ContainerInterface $container)
    {
        $options = $container->get(ContentNegotiationOptions::class);
        $httpOverrideMethods = $options->getHttpOverrideMethods();
        $listener = new HttpMethodOverrideListener($httpOverrideMethods);

        return $listener;
    }
}
