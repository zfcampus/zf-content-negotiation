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
        if (!method_exists($request, 'getHeaders')) {
            // Not an HTTP request; nothing to do
            return;
        }

        $headers = $request->getHeaders();

        $matched = false;
        if (is_string($this->config[$controllerName])) {
            $matched = $this->validateContentType($this->config[$controllerName], $headers);
        } elseif (is_array($this->config[$controllerName])) {
            foreach ($this->config[$controllerName] as $whitelistType) {
                $matched = $this->validateContentType($whitelistType, $headers);
                if ($matched) {
                    break;
                }
            }
        }

        if (!$matched) {
            throw new DomainException('Cannot honor Accept type specified', 406);
        }
    }

    protected function validateContentType($match, $headers)
    {
        if (!$headers->has('accept')) {
            return false;
        }

        $accept = $headers->get('accept');
        if ($accept->match($match)
            && !strstr($accept->getFieldValue(), '*/*')
        ) {
            return true;
        }

        return false;
    }
}
