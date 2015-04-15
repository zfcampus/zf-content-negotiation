<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation;

use Zend\Mvc\MvcEvent;

class Module
{
    /**
     * {@inheritDoc}
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/',
                ),
            ),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * {@inheritDoc}
     */
    public function onBootstrap($e)
    {
        $app      = $e->getApplication();
        $services = $app->getServiceManager();
        $em       = $app->getEventManager();

        $em->attach(MvcEvent::EVENT_ROUTE, $services->get('ZF\ContentNegotiation\ContentTypeListener'), -625);
        $em->attachAggregate($services->get('ZF\ContentNegotiation\AcceptFilterListener'));
        $em->attachAggregate($services->get('ZF\ContentNegotiation\ContentTypeFilterListener'));

        $sem = $em->getSharedManager();
        $sem->attach(
            'Zend\Stdlib\DispatchableInterface',
            MvcEvent::EVENT_DISPATCH,
            $services->get('ZF\ContentNegotiation\AcceptListener'),
            -10
        );
    }
}
