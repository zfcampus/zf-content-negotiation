<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation;

use Zend\Mvc\Controller\Plugin\AcceptableViewModelSelector;
use Zend\Mvc\InjectApplicationEventInterface;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\ModelInterface as ViewModelInterface;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

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
     * @return null|ApiProblemResponse
     */
    public function __invoke(MvcEvent $e)
    {
        $request = $e->getRequest();
        if (! method_exists($request, 'getHeaders')) {
            // Should only trigger on HTTP requests
            return;
        }

        $result = $e->getResult();
        if (!is_array($result) && (!$result instanceof ViewModel)) {
            // We will only attempt to re-cast ContentNegotiation\ViewModel
            // results or arrays to what the AcceptableViewModelSelector gives
            // us. Anything else, we cannot handle.
            return;
        }

        $controller = $e->getTarget();
        if (!$controller instanceof InjectApplicationEventInterface) {
            // The AcceptableViewModelSelector needs a controller that is
            // event-aware in order to work; if it's not, we cannot do
            // anything more.
            return;
        }
        $selector  = $this->selector;
        $selector->setController($controller);

        $criteria = $e->getParam('ZFContentNegotiation');

        // If the criteria from the ZFContentNegotiation parameter is a string,
        // attempt to get it via a selector.
        if (is_string($criteria)) {
            $criteria = $this->getCriteria($criteria);
        }

        // If we have no criteria, derive it from configuration and/or any set fallbacks
        if (!$criteria) {
            $fallbackConfig = $e->getParam('ZFContentNegotiationFallback');
            $controllerName = $e->getRouteMatch()->getParam('controller');

            $criteria = $this->getSelectorCriteria($fallbackConfig, $controllerName);
        }

        // Retrieve a view model based on the criteria
        $useDefault = false;
        if (!$criteria || empty($criteria)) {
            $useDefault = true;
        }
        $viewModel = $selector($criteria, $useDefault);

        if (!$viewModel instanceof ViewModelInterface) {
            return new ApiProblemResponse(new ApiProblem(406, 'Unable to resolve Accept header to a representation'));
        }

        // Populate the view model with the result...
        $this->populateViewModel($result, $viewModel, $e);
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
        if (empty($this->controllerConfig)) {
            return $this->getCriteria($fallbackConfig);
        }

        // get the controllers from the content-neg configuration
        $controllers = $this->controllerConfig;

        // if there is no config for this controller, move on
        if (!$controllerName || !isset($controllers[$controllerName])) {
            return $this->getCriteria($fallbackConfig);
        }

        // Retrieve the criteria; if none found, or invalid, use the fallback.
        $criteria = $controllers[$controllerName];

        return $this->getCriteria($criteria) ?: $this->getCriteria($fallbackConfig);
    }

    /**
     * Populate the view model returned by the AcceptableViewModelSelector from the result
     *
     * If the result is a ViewModel, we "re-cast" it by copying over all
     * values/settings/etc from the original.
     *
     * If the result is an array, we pass those values as the view model variables.
     *
     * @param  array|ViewModel $result
     * @param  ViewModelInterface $viewModel
     * @param  MvcEvent $e
     */
    protected function populateViewModel($result, ViewModelInterface $viewModel, MvcEvent $e)
    {
        if ($result instanceof ViewModel) {
            // "Re-cast" content-negotiation view models to the view model type
            // selected by the AcceptableViewModelSelector

            $viewModel->setVariables($result->getVariables());
            $viewModel->setTemplate($result->getTemplate());
            $viewModel->setOptions($result->getOptions());
            $viewModel->setCaptureTo($result->captureTo());
            $viewModel->setTerminal($result->terminate());
            $viewModel->setAppend($result->isAppend());
            if ($result->hasChildren()) {
                foreach ($result->getChildren() as $child) {
                    $viewModel->addChild($child);
                }
            }

            $e->setResult($viewModel);
            return;
        }

        // At this point, the result is an array; use it to populate the view
        // model variables
        $viewModel->setVariables($result);
        $e->setResult($viewModel);
    }

    /**
     * Return criteria
     *
     * If the criteria is an array, return it directly.
     *
     * If the criteria is a string, attempt to look it up in the registered selectors;
     * if found, return that criteria.
     *
     * Otherwise, return nothing.
     *
     * @param  string|array $criteria
     * @return array|null
     */
    protected function getCriteria($criteria)
    {
        // if it's an array, that means we have direct configuration
        if (is_array($criteria)) {
            return $criteria;
        }

        // if it's a string, we should try to resolve that key to a reusable selector set
        if (is_string($criteria) && isset($this->selectorsConfig[$criteria])) {
            $criteria = $this->selectorsConfig[$criteria];
            if (!empty($criteria)) {
                return $criteria;
            }
        }
    }
}
