<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\ContentNegotiation\AcceptFilterListener;

class AcceptFilterListenerFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $listener = new AcceptFilterListener();

        /* @var $options \ZF\ContentNegotiation\ContentNegotiationOptions */
        $options = $serviceLocator->get('ZF\ContentNegotiation\ContentNegotiationOptions');

        $listener->setConfig($options->getAcceptWhitelist());

        return $listener;
    }
}
