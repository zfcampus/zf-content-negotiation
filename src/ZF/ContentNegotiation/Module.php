<?php

namespace ZF\ContentNegotiation;

use Zend\Mvc\Controller\Plugin\AcceptableViewModelSelector;

class Module
{
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

    public function getConfig()
    {
        return include __DIR__ . '/../../../config/module.config.php';
    }

    public function getServiceConfig()
    {
        return array('factories' => array(
            'ZF\ContentNegotiation\AcceptListener' => function ($services) {
                $config = array();
                if ($services->has('Config')) {
                    $appConfig = $services->get('Config');
                    if (isset($appConfig['zf-content-negotiation'])
                        && is_array($appConfig['zf-content-negotiation'])
                    ) {
                        $config = $appConfig['zf-content-negotiation'];
                    }
                }

                $selector = null;
                if ($services->has('ControllerPluginManager')) {
                    $plugins = $services->get('ControllerPluginManager');
                    if ($plugins->has('AcceptableViewModelSelector')) {
                        $selector = $plugins->get('AcceptableViewModelSelector');
                    }
                }
                if (null === $selector) {
                    $selector = new AcceptableViewModelSelector();
                }
                return new AcceptListener($selector, $config);
            },
        ));
    }

    public function onBootstrap($e)
    {
        $app      = $e->getApplication();
        $services = $app->getServiceManager();
        $em       = $app->getEventManager();

        $em->attach(MvcEvent::EVENT_ROUTE, new ContentTypeListener(), -99);

        $sem = $em->getSharedManager();
        $sem->attach(
            'Zend\Stdlib\DispatchableInterface',
            MvcEvent::EVENT_DISPATCH,
            $services->get('ZF\ContentNegotiation\AcceptListener'),
            -10
        );
    }
}
