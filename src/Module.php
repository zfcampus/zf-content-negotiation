<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation;

use Zend\Mvc\MvcEvent;
use Zend\Stdlib\DispatchableInterface;
use ZF\ContentNegotiation\AcceptListener;
use ZF\ContentNegotiation\AcceptFilterListener;
use ZF\ContentNegotiation\ContentTypeFilterListener;
use ZF\ContentNegotiation\ContentTypeListener;

class Module
{
    /**
     * Return module-specific configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Listen to bootstrap event.
     *
     * Attaches the ContentTypeListener, AcceptFilterListener, and
     * ContentTypeFilterListener to the application event manager.
     *
     * Attaches the AcceptListener as a shared listener for controller dispatch
     * events.
     *
     * @param MvcEvent $e
     * @return void
     */
    public function onBootstrap(MvcEvent $e)
    {
        $app = $e->getApplication();
        $services = $app->getServiceManager();
        $eventManager = $app->getEventManager();

        $eventManager->attach(MvcEvent::EVENT_ROUTE, $services->get(ContentTypeListener::class), -625);

        $services->get(AcceptFilterListener::class)->attach($eventManager);
        $services->get(ContentTypeFilterListener::class)->attach($eventManager);

        $contentNegotiationOptions = $services->get(ContentNegotiationOptions::class);
        if ($contentNegotiationOptions->getXHttpMethodOverrideEnabled()) {
            $services->get(HttpMethodOverrideListener::class)->attach($eventManager);
        }

        $sharedEventManager = $eventManager->getSharedManager();
        $sharedEventManager->attach(
            DispatchableInterface::class,
            MvcEvent::EVENT_DISPATCH,
            $services->get(AcceptListener::class),
            -10
        );
    }
}
