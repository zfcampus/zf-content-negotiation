<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\ContentNegotiation\ContentTypeFilterListener;

class ContentTypeFilterListenerFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $listener = new ContentTypeFilterListener();

        /* @var $options \ZF\ContentNegotiation\ContentNegotiationOptions */
        $options = $serviceLocator->get('ZF\ContentNegotiation\ContentNegotiationOptions');

        $listener->setConfig($options->getContentTypeWhitelist());

        return $listener;
    }
}
