<?php

namespace ZF\ContentNegotiation;

use Zend\Mvc\MvcEvent;
use Zend\Http\Request;

class ContentNegotiationListener
{

    public function __invoke(MvcEvent $e)
    {
        /* @var $request \Zend\Http\Request */
        $request = $e->getRequest();
        $routeMatch = $e->getRouteMatch();

        $parameterData = new ParameterDataContainer();

        // route parameters:
        $routeParams = $routeMatch->getParams();
        $parameterData->setRouteParams($routeParams);

        // query parameters:
        $parameterData->setQueryParams($_GET);

        // body parameters:
        $bodyParams = array();

        /** @var \Zend\Http\Header\ContentType $contentType */
        $contentType = $request->getHeader('Content-type');

        if ($contentType && strtolower($contentType->getFieldValue()) == 'application/json') {
            $bodyParams = json_decode($request->getContent(), true);
        } else {
            if ($request->isPost()) {
                $bodyParams = $_POST;
            } elseif ($contentType && strtolower($contentType->getFieldValue()) == 'application/x-www-form-urlencoded') {
                parse_str($request->getContent(), $bodyParams);
            }
        }

        $parameterData->setBodyParams($bodyParams);

        /** @var \Zend\Http\Header\Accept $accept */
//        $accept = $request->getHeader('Accept');


        $e->setParam('ZFContentNegotiationParameterData', $parameterData);
    }
}