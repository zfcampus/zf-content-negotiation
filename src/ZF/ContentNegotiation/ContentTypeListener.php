<?php

namespace ZF\ContentNegotiation;

use Zend\Mvc\MvcEvent;
use Zend\Http\Request;

class ContentTypeListener
{
    public function __invoke(MvcEvent $e)
    {
        $request       = $e->getRequest();
        if (!method_exists($request, 'getHeaders')) {
            // Not an HTTP request; nothing to do
            return;
        }

        $routeMatch    = $e->getRouteMatch();
        $parameterData = new ParameterDataContainer();

        // route parameters:
        $routeParams = $routeMatch->getParams();
        $parameterData->setRouteParams($routeParams);

        // query parameters:
        $parameterData->setQueryParams($request->getQuery()->toArray());

        // body parameters:
        $bodyParams  = array();
        $contentType = $request->getHeader('Content-type');
        switch ($request->getMethod()) {
            case $request::METHOD_POST:
                if ($contentType && $contentType->match('application/json')) {
                    $bodyParams = json_decode($request->getContent(), true);
                    break;
                }

                $bodyParams = $request->getPost()->toArray();
                break;
            case $request::METHOD_PATCH:
            case $request::METHOD_PUT:
                $content = $request->getContent();
                if ($contentType && $contentType->match('application/json')) {
                    $bodyParams = json_decode($content, true);
                    break;
                }

                // Stolen from AbstractRestfulController
                parse_str($content, $bodyParams);
                if (!is_array($bodyParams)
                    || (1 == count($bodyParams) && isset($bodyParams[0]))
                ) {
                    $bodyParams = $content;
                }
                break;
            default:
                break;
        }

        $parameterData->setBodyParams($bodyParams);
        $e->setParam('ZFContentNegotiationParameterData', $parameterData);
    }
}
