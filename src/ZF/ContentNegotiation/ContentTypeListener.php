<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation;

use Zend\Mvc\MvcEvent;
use Zend\Http\Request;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

class ContentTypeListener
{
    protected $jsonErrors = [
        JSON_ERROR_DEPTH          => 'Maximum stack depth exceeded',
        JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
        JSON_ERROR_CTRL_CHAR      => 'Unexpected control character found',
        JSON_ERROR_SYNTAX         => 'Syntax error, malformed JSON',
        JSON_ERROR_UTF8           => 'Malformed UTF-8 characters, possibly incorrectly encoded',
    ];

    /**
     * Perform content negotiation
     *
     * For HTTP methods expecting body content, attempts to match the incoming
     * content-type against the list of allowed content types, and then performs
     * appropriate content deserialization.
     *
     * If an error occurs during deserialization, an ApiProblemResponse is 
     * returned, indicating an issue with the submission.
     * 
     * @param MvcEvent $e 
     * @return null|ApiProblemResponse
     */
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
                    $bodyParams = $this->decodeJson($request->getContent());
                    break;
                }

                $bodyParams = $request->getPost()->toArray();
                break;
            case $request::METHOD_PATCH:
            case $request::METHOD_PUT:
                $content = $request->getContent();
                if ($contentType && $contentType->match('application/json')) {
                    $bodyParams = $this->decodeJson($content);
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

        if ($bodyParams instanceof ApiProblemResponse) {
            return $bodyParams;
        }

        $parameterData->setBodyParams($bodyParams);
        $e->setParam('ZFContentNegotiationParameterData', $parameterData);
    }

    /**
     * Attempt to decode a JSON string
     *
     * Decodes a JSON string and returns it; if invalid, returns
     * an ApiProblemResponse.
     *
     * @param string $json
     * @return mixed|ApiProblemResponse
     */
    public function decodeJson($json)
    {
        $data = json_decode($json, true);
        if (null !== $data) {
            return $data;
        }
        $error = json_last_error();
        if ($error === JSON_ERROR_NONE) {
            return $data;
        }

        $message = array_key_exists($error, $this->jsonErrors) ? $this->jsonErrors[$error] : 'Unknown error';
        return new ApiProblemResponse(
            new ApiProblem(400, sprintf('JSON decoding error: %s', $message))
        );
    }
}
