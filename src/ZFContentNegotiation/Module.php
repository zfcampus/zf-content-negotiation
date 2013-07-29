<?php

namespace ZFContentNegotiation;

use Zend\Mvc\MvcEvent;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ServiceManager\ServiceLocatorInterface;


class Module implements AutoloaderProviderInterface /*, ConfigProviderInterface */
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $sm;

    /*
    public function onBootstrap(MvcEvent $e)
    {
        $this->sm = $e->getApplication()->getServiceManager();
    }
    */

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

    /**
     * Bootstrap time
     *
     * @param MvcEvent $e
     */
    public function onBootstrap($e)
    {
        $app = $e->getApplication();
        $em = $app->getEventManager();

        // setup route listeners
        $em->attach(MvcEvent::EVENT_ROUTE, new ContentNegotiationListener(), -99);
    }

}
