<?php

namespace ZF\ContentNegotiation;

use Zend\EventManager\SharedListenerAggregateInterface;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\ArrayUtils;
use ZF\ApiProblem\Exception\DomainException;

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

        $request           = $e->getRequest();
        $headers           = $request->getHeaders();
        $contentTypeHeader = false;
        if ($headers->has('content-type')) {
            $contentTypeHeader = $headers->get('content-type');
            $value             = $contentTypeHeader->getFieldValue();
            $value             = explode(';', $value, 2);
            $contentTypeHeader = array_shift($value);
            $contentTypeHeader = strtolower($contentTypeHeader);
        }
            
        if (is_string($this->config[$controllerName])) {
            $this->validateContentType($contentTypeHeader, $this->config[$controllerName]);
            return;
        }

        if (is_array($this->config[$controllerName])) {
            foreach ($this->config[$controllerName] as $whitelistType) {
                $this->validateContentType($contentTypeHeader, $whitelistType);
            }
            return;
        }
    }

    /**
     * Validate that the content type received matches that in the whitelist
     * 
     * @param  string $received 
     * @param  string $allowed 
     */
    protected function validateContentType($received, $allowed)
    {
        if (!$received) {
            throw new DomainException('Invalid content-type specified', 415);
        }
        if (strtolower($allowed) !== $received) {
            throw new DomainException('Invalid content-type specified', 415);
        }
    }
}
