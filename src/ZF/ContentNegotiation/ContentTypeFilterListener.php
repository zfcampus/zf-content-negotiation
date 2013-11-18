<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation;

use Zend\EventManager\SharedListenerAggregateInterface;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\ArrayUtils;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

class ContentTypeFilterListener implements SharedListenerAggregateInterface
{
    /**
     * Whitelist configuration
     * @var array
     */
    protected $config = array();

    /**
     * @var \Zend\Stdlib\CallbackHandler
     */
    protected $listeners = array();

    /**
     * Attach to dispatch event at high priority
     * 
     * @param  SharedEventManagerInterface $events 
     */
    public function attachShared(SharedEventManagerInterface $events)
    {
        $this->listeners[] = $events->attach('Zend\Stdlib\DispatchableInterface', MvcEvent::EVENT_DISPATCH, array($this, 'onDispatch'), 100);
    }

    /**
     * Detach listeners
     * 
     * @param  SharedEventManagerInterface $events 
     */
    public function detachShared(SharedEventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach('Zend\Stdlib\DispatchableInterface', $listener)) {
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
     */
    public function onDispatch(MvcEvent $e)
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
        $request           = $e->getRequest();
        if (!method_exists($request, 'getHeaders')) {
            // Not an HTTP request; nothing to do
            return;
        }

        $requestBody = $request->getContent();
        if (empty($requestBody)) {
            return;
        }

        $headers           = $request->getHeaders();
        $contentTypeHeader = false;
        if (!$headers->has('content-type')) {
            return new ApiProblemResponse(new ApiProblem(415, 'Invalid content-type specified'));
        }

        $contentTypeHeader = $headers->get('content-type');
            
        $matched = $contentTypeHeader->match($this->config[$controllerName]);

        if (false === $matched) {
            return new ApiProblemResponse(new ApiProblem(415, 'Invalid content-type specified'));
        }
    }
}
