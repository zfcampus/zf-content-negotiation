<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\Mvc\Controller\Plugin\AcceptableViewModelSelector;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use ZF\ContentNegotiation\AcceptListener;
use ZF\ContentNegotiation\ContentNegotiationOptions;

class AcceptListenerFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string             $requestedName
     * @param  null|array         $options
     *
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = NULL)
    {
        /* @var $options \ZF\ContentNegotiation\ContentNegotiationOptions */
        $options = $container->get(ContentNegotiationOptions::class);

        $selector = null;
        if ($container->has('ControllerPluginManager')) {
            $plugins = $container->get('ControllerPluginManager');
            if ($plugins->has('AcceptableViewModelSelector')) {
                $selector = $plugins->get('AcceptableViewModelSelector');
            }
        }

        if (null === $selector) {
            $selector = new AcceptableViewModelSelector();
        }

        return new AcceptListener($selector, $options->toArray());
    }

}
