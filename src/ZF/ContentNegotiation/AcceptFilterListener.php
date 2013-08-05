<?php

namespace ZF\ContentNegotiation;

use Zend\EventManager\SharedListenerAggregateInterface;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\ArrayUtils;
use ZF\ApiProblem\Exception\DomainException;

class AcceptFilterListener extends ContentTypeFilterListener
{
    /**
     * Test if the accept content-type received is allowable.
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

        $request = $e->getRequest();
        $headers = $request->getHeaders();

        if (is_string($this->config[$controllerName])) {
            $this->validateContentType($this->config[$controllerName], $headers);
            return;
        }

        if (is_array($this->config[$controllerName])) {
            foreach ($this->config[$controllerName] as $whitelistType) {
                $this->validateContentType($whitelistType, $headers);
            }
            return;
        }
    }

    protected function validateContentType($match, $headers)
    {
        if (!$headers->has('accept')) {
            throw new DomainException('Cannot honor Accept type specified', 406);
        }

        $accept = $headers->get('accept');
        if (!$accept->match($match)) {
            throw new DomainException('Cannot honor Accept type specified', 406);
        }
    }
}
