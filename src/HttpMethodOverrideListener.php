<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation;

use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\MvcEvent;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;
use Zend\Http\Request as HttpRequest;

class HttpMethodOverrideListener extends AbstractListenerAggregate
{
    /**
     * @var array
     */
    protected $httpMethodOverride = [];

    /**
     * HttpMethodOverrideListener constructor.
     *
     * @param array $httpMethodOverride
     */
    public function __construct(array $httpMethodOverride)
    {
        $this->httpMethodOverride = $httpMethodOverride;
    }

    /**
     * Priority is set very high (should be executed before all other listeners that rely on the request method value).
     * TODO: Check priority value, maybe value should be even higher??
     *
     * @param EventManagerInterface $events
     * @param int                   $priority
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, [$this, 'onRoute'], -40);
    }

    /**
     * Checks for X-HTTP-Method-Override header and sets header inside request object.
     *
     * @param  MvcEvent $event
     * @return void|ApiProblemResponse
     */
    public function onRoute(MvcEvent $event)
    {
        $request = $event->getRequest();

        if (! $request instanceof HttpRequest) {
            return;
        }

        if (! $request->getHeaders()->has('X-HTTP-Method-Override')) {
            return;
        }

        $method = $request->getMethod();

        if (! array_key_exists($method, $this->httpMethodOverride)) {
            return new ApiProblemResponse(new ApiProblem(
                400,
                sprintf('Overriding %s method with X-HTTP-Method-Override header is not allowed', $method)
            ));
        }

        $header = $request->getHeader('X-HTTP-Method-Override');
        $overrideMethod = $header->getFieldValue();
        $allowedMethods = $this->httpMethodOverride[$method];

        if (! in_array($overrideMethod, $allowedMethods)) {
            return new ApiProblemResponse(new ApiProblem(
                400,
                sprintf('Illegal override method %s in X-HTTP-Method-Override header', $overrideMethod)
            ));
        }

        $request->setMethod($overrideMethod);
    }
}
