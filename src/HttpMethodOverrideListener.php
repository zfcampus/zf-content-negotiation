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
    protected $methods = [
        HttpRequest::METHOD_HEAD,
        HttpRequest::METHOD_POST,
        HttpRequest::METHOD_PUT,
        HttpRequest::METHOD_DELETE,
        HttpRequest::METHOD_PATCH,
    ];

    /**
     * Priority is set very high (should be executed before all other listeners that rely on the request method value).
     * TODO: Check priority value, maybe value should be even higher??
     *
     * @param  EventManagerInterface $events
     * @param int                    $priority
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

        $header = $request->getHeader('X-HTTP-Method-Override');

        $method = $header->getFieldValue();

        if (! in_array($method, $this->methods)) {
            return new ApiProblemResponse(new ApiProblem(
                400,
                'Unrecognized method in X-HTTP-Method-Override header'
            ));
        }

        $request->setMethod($method);
    }
}
