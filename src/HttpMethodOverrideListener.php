<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation;

use Zend\Mvc\MvcEvent;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;
use Zend\Http\Request as HttpRequest;

class HttpMethodOverrideListener
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
     * Checks for X-HTTP-Method-Override header and sets header inside request object.
     *
     * @param  MvcEvent $event
     * @return void|ApiProblemResponse
     */
    public function __invoke(MvcEvent $event)
    {
        $request = $event->getRequest();

        if(!$request instanceof HttpRequest){
            return;
        }

        if(!$request->getHeaders()->has('X-HTTP-Method-Override')){
            return;
        }

        $header = $request->getHeader('X-HTTP-Method-Override');

        $method = $header->getFieldValue();

        if (!in_array($method, $this->methods)) {
            return new ApiProblemResponse(new ApiProblem(
                400,
                'unrecognized method in X-HTTP-Method-Ovverride header'
            ));
        }

        $request->setMethod($method);
    }
}