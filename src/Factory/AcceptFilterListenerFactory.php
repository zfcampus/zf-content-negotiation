<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation\Factory;

use Interop\Container\ContainerInterface;
use ZF\ContentNegotiation\AcceptFilterListener;
use ZF\ContentNegotiation\ContentNegotiationOptions;

class AcceptFilterListenerFactory
{
    /**
     * @param  ContainerInterface $container
     * @return AcceptFilterListener
     */
    public function __invoke(ContainerInterface $container)
    {
        $listener = new AcceptFilterListener();

        $options = $container->get(ContentNegotiationOptions::class);
        $listener->setConfig($options->getAcceptWhitelist());

        return $listener;
    }
}
