<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation;

use Zend\Loader\StandardAutoloader;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\DispatchableInterface;
use ZF\ContentNegotiation\AcceptListener;
use ZF\ContentNegotiation\AcceptFilterListener;
use ZF\ContentNegotiation\ContentTypeFilterListener;
use ZF\ContentNegotiation\ContentTypeListener;

class Module
{
    /**
     * {@inheritDoc}
     */
    public function getAutoloaderConfig()
    {
        return [
            StandardAutoloader::class => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/',
                ],
            ],
        ];
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
    public function onBootstrap(MvcEvent $e)
    {
        $app = $e->getApplication();
        $services = $app->getServiceManager();
        $eventManager = $app->getEventManager();

        $eventManager->attach(MvcEvent::EVENT_ROUTE, $services->get(ContentTypeListener::class), -625);

        /** @var AcceptFilterListener $acceptFilterListener */
        $acceptFilterListener = $services->get(AcceptFilterListener::class);
        /** @var ContentTypeFilterListener $contentTypeFilterListener */
        $contentTypeFilterListener = $services->get(ContentTypeFilterListener::class);
        
        
        $acceptFilterListener->attach($eventManager);
        $contentTypeFilterListener->attach($eventManager);

        
        $sharedEventManager = $eventManager->getSharedManager();
        $sharedEventManager->attach(
            DispatchableInterface::class,
            MvcEvent::EVENT_DISPATCH,
            $services->get(AcceptListener::class),
            -10
        );
    }
}
