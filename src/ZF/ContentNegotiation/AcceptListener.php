<?php

namespace ZF\ContentNegotiation;

use Zend\Mvc\MvcEvent;

class AcceptListener
{
    public function __invoke(MvcEvent $e)
    {
        $app = $e->getApplication();

        $controller = $e->getTarget();
        $controllerConfig = $e->getParam('ZFContentNegotiation');

        if (!$controllerConfig) {
            $services = $app->getServiceManager();
            $appConfig = $services->get('Config');


            if (!isset($appConfig['zf-content-negotiation']) || !isset($appConfig['zf-content-negotiation']['controllers'])) {
                goto FALLBACK;
            }

            // get the controllers from the content-neg configuration
            $controllers = $appConfig['zf-content-negotiation']['controllers'];
            $controllerName = $e->getRouteMatch()->getParam('controller');

            // if there is no config for this controller, move on
            if (!$controllerName || !isset($controllers[$controllerName])) {
                goto FALLBACK;
            }

            // if its an array, that means its direct configuration
            if (is_array($controllers[$controllerName])) {
                $controllerConfig = $controllers[$controllerName];
            } elseif (is_string($controllers[$controllerName])) {

                // if its a string, we should try to resolve that key to a rusable selector set
                if (isset($appConfig['zf-content-negotiation']['selectors'])) {
                    $selectors = $appConfig['zf-content-negotiation']['selectors'];
                    if (isset($selectors[$controllers[$controllerName]])) {
                        $controllerConfig = $selectors[$controllers[$controllerName]];
                    }
                }

            }
        }

        FALLBACK:

        if (!$controllerConfig) {
            $controllerConfig = $e->getParam('ZFContentNegotiationFallback');
        }

        if ($controllerConfig) {
            $result = $e->getResult();
            /** @var \Zend\View\Model\ViewModel $viewModel */
            $viewModel = $controller->acceptableViewModelSelector($controllerConfig);
            if (is_array($result)) {
                $viewModel->setVariables($result);
            } elseif ($result instanceof ViewModel) {
                $viewModel->setVariables($result->getVariables());
            }

            $viewModel->setTerminal(true);
            $e->setResult($viewModel);
        }
    }
}