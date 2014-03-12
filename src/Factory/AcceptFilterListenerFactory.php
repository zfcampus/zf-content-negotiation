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
        $config   = array();

        if ($serviceLocator->has('Config')) {
            $moduleConfig = false;
            $appConfig    = $serviceLocator->get('Config');
            if (isset($appConfig['zf-content-negotiation'])
                && is_array($appConfig['zf-content-negotiation'])
            ) {
                $moduleConfig = $appConfig['zf-content-negotiation'];
            }

            if ($moduleConfig
                && isset($moduleConfig['accept_whitelist'])
                && is_array($moduleConfig['accept_whitelist'])
            ) {
                $config = $moduleConfig['accept_whitelist'];
            }
        }

        if (!empty($config)) {
            $listener->setConfig($config);
        }

        return $listener;
    }
}
