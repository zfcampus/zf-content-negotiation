<?php

namespace ZF\ContentNegotiation;

use Zend\Mvc\Controller\Plugin\AcceptableViewModelSelector;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\ModelInterface as ViewModelInterface;

class AcceptListener
{
    /**
     * @var AcceptableViewModelSelector
     */
    protected $selector;

    /**
     * @var array
     */
    protected $controllerConfig = array();

    /**
     * @var array
     */
    protected $selectorsConfig = array();

    /**
     * @param AcceptableViewModelSelector $selector 
     * @param array $config 
     */
    public function __construct(AcceptableViewModelSelector $selector, array $config)
    {
        $this->selector = $selector;

        if (isset($config['controllers'])
            && is_array($config['controllers'])
        ) {
            $this->controllerConfig = $config['controllers'];
        }

        if (isset($config['selectors'])
            && is_array($config['selectors'])
        ) {
            $this->selectorsConfig = $config['selectors'];
        }
    }

    /**
     * @param  MvcEvent $e 
     */
    public function __invoke(MvcEvent $e)
    {
        $criteria = $e->getParam('ZFContentNegotiation');

        // If we have no criteria, derive it from configuration and/or any set fallbacks
        if (!$criteria) {
            $fallbackConfig = $e->getParam('ZFContentNegotiationFallback');
            $controllerName = $e->getRouteMatch()->getParam('controller');

            $criteria = $this->getSelectorCriteria($fallbackConfig, $controllerName);
        }

        // Retrieve a view model based on the criteria
        $selector  = $this->selector;
        $viewModel = $selector($criteria);

        // Populate the view model with the result...
        $this->populateViewModel($result, $viewModel);
    }

    /**
     * Derive the view model selector criteria
     *
     * Try and determine the view model selection criteria based on the configuration
     * for the current controller service name, using a fallback if it exists.
     * 
     * @param  null|array $fallbackConfig 
     * @param  string $controllerName 
     * @return null|array
     */
    protected function getSelectorCriteria($fallbackConfig, $controllerName)
    {
        $criteria = null;

        if (empty($this->controllerConfig)) {
            return $fallbackConfig;
        }

        // get the controllers from the content-neg configuration
        $controllers = $this->controllerConfig;

        // if there is no config for this controller, move on
        if (!$controllerName || !isset($controllers[$controllerName])) {
            return $fallbackConfig;
        }

        $criteria = $controllers[$controllerName];

        // if it's an array, that means we have direct configuration
        if (is_array($criteria)) {
            return $criteria;
        }

        // if it's a string, we should try to resolve that key to a reusable selector set
        if (is_string($criteria)) {
            if (isset($this->selectorsConfig[$criteria])) {
                $criteria = $this->selectorsConfig[$criteria];
                if (!empty($criteria)) {
                    return $criteria;
                }
                return $fallbackConfig;
            }
        }

        return $fallbackConfig;
    }

    protected function populateViewModel($result, ViewModelInterface $viewModel)
    {
        if ($result instanceof ViewModelInterface) {
            // If the result is of the same type as the selected view model, do nothing
            if (get_class($result) == get_class($viewModel)) {
                return;
            }

            // Otherwise, "re-cast" the view model
            $viewModel->setVariables($result->getVariables());
            $viewModel->setTemplate($result->getTemplate());
            $e->setResult($viewModel);
            return;
        }

        // If result is an array, use it to populate the view model variables
        if (is_array($result)) {
            $viewModel->setVariables($result);
            $e->setResult($viewModel);
            return;
        }

        // For all other result types, set the "result" variable to the result value
        $viewModel->setVariable('result', $result);
        $e->setResult($viewModel);
    }
}
