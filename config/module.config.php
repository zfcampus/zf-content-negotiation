<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

return array(
    'service_manager' => array(
        'factories' => array(
            'ZF\ContentNegotiation\AcceptListener'            => 'ZF\ContentNegotiation\Factory\AcceptListenerFactory',
            'ZF\ContentNegotiation\AcceptFilterListener'      => 'ZF\ContentNegotiation\Factory\AcceptFilterListenerFactory',
            'ZF\ContentNegotiation\ContentTypeFilterListener' => 'ZF\ContentNegotiation\Factory\ContentTypeFilterListenerFactory',
        )
    ),

    'zf-content-negotiation' => array(
        // ???? Comment about it ?
        'controllers' => array(),

        // ???? Comment about it ?
        'selectors'   => array(
            'Json' => array(
                'ZF\ContentNegotiation\JsonModel' => array(
                    'application/json',
                    'application/*+json',
                ),
            ),
        ),

        // Array of controller service name => allowed accept header pairs.
        // The allowed content type may be a string, or an array of strings.
        'accept_whitelist' => array(),

        // Array of controller service name => allowed content type pairs.
        // The allowed content type may be a string, or an array of strings.
        'content_type_whitelist' => array(),
    ),

    'controller_plugins' => array(
        'invokables' => array(
            'routeParam'  => 'ZF\ContentNegotiation\ControllerPlugin\RouteParam',
            'queryParam'  => 'ZF\ContentNegotiation\ControllerPlugin\QueryParam',
            'bodyParam'   => 'ZF\ContentNegotiation\ControllerPlugin\BodyParam',
            'routeParams' => 'ZF\ContentNegotiation\ControllerPlugin\RouteParams',
            'queryParams' => 'ZF\ContentNegotiation\ControllerPlugin\QueryParams',
            'bodyParams'  => 'ZF\ContentNegotiation\ControllerPlugin\BodyParams',
        )
    )
);
