<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation\Factory;

use Interop\Container\ContainerInterface;
use Zend\Mvc\Controller\Plugin\AcceptableViewModelSelector;
use ZF\ContentNegotiation\AcceptListener;
use ZF\ContentNegotiation\ContentNegotiationOptions;

class AcceptListenerFactory
{
    /**
     * @param  ContainerInterface $container
     * @return AcceptListener
     */
    public function __invoke(ContainerInterface $container)
    {
        return new AcceptListener(
            $this->getAcceptableViewModelSelector($container),
            $container->get(ContentNegotiationOptions::class)->toArray()
        );
    }

    /**
     * Retrieve or generate the AcceptableViewModelSelector plugin instance.
     *
     * @param  ContainerInterface $container
     * @return AcceptableViewModelSelector
     */
    private function getAcceptableViewModelSelector(ContainerInterface $container)
    {
        if (! $container->has('ControllerPluginManager')) {
            return new AcceptableViewModelSelector();
        }

        $plugins = $container->get('ControllerPluginManager');
        if (! $plugins->has('AcceptableViewModelSelector')) {
            return new AcceptableViewModelSelector();
        }

        return $plugins->get('AcceptableViewModelSelector');
    }
}
