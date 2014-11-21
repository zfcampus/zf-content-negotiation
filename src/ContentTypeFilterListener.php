<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\ArrayUtils;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

class ContentTypeFilterListener implements ListenerAggregateInterface
{
    /**
     * Whitelist configuration
     *
     * @var array
     */
    protected $config = array();

    /**
     * @var array
     */
    protected $listeners = array();

    /**
     * @param  EventManagerInterface $events
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, array($this, 'onRoute'), -625);
    }

    /**
     * Detach listeners
     *
     * @param  EventManagerInterface $events
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    /**
     * Set whitelist configuration
     *
     * @param  array $config
     * @return self
     */
    public function setConfig(array $config)
    {
        $this->config = ArrayUtils::merge($this->config, $config);
        return $this;
    }

    /**
     * Test if the content-type received is allowable.
     *
     * @param  MvcEvent $e
     * @return null|ApiProblemResponse
     */
    public function onRoute(MvcEvent $e)
    {
        if (empty($this->config)) {
            return;
        }

        $controllerName = $e->getRouteMatch()->getParam('controller');
        if (!isset($this->config[$controllerName])) {
            return;
        }

        // Only worry about content types on HTTP methods that submit content
        // via the request body.
        $request = $e->getRequest();
        if (!method_exists($request, 'getHeaders')) {
            // Not an HTTP request; nothing to do
            return;
        }

        $requestBody = $request->getContent();
        if (empty($requestBody)) {
            return;
        }

        $headers = $request->getHeaders();
        if (!$headers->has('content-type')) {
            return new ApiProblemResponse(
                new ApiProblem(415, 'Invalid content-type specified')
            );
        }

        $contentTypeHeader = $headers->get('content-type');

        $matched = $contentTypeHeader->match($this->config[$controllerName]);

        if (false === $matched) {
            return new ApiProblemResponse(
                new ApiProblem(415, 'Invalid content-type specified')
            );
        }
    }
}
