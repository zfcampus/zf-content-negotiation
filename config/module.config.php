<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

return array(
    'zf-content-negotiation' => array(
        'controllers' => array(),
        'selectors' => array(
            'Json' => array(
                'ZF\ContentNegotiation\JsonModel' => array(
                    'application/json',
                    'application/*+json',
                ),
            ),
        ),
        'accept-whitelist' => array(
            // Array of controller service name => allowed accept header pairs.
            // The allowed content type may be a string, or an array of strings.
        ),
        'content-type-whitelist' => array(
            // Array of controller service name => allowed content type pairs.
            // The allowed content type may be a string, or an array of strings.
        ),
    ),
    'controller_plugins' => array(
        'invokables' => array(
            'routeParam'                 => 'ZF\ContentNegotiation\ControllerPlugin\RouteParam',
            'queryParam'                 => 'ZF\ContentNegotiation\ControllerPlugin\QueryParam',
            'bodyParam'                  => 'ZF\ContentNegotiation\ControllerPlugin\BodyParam',
            'routeParams'                => 'ZF\ContentNegotiation\ControllerPlugin\RouteParams',
            'queryParams'                => 'ZF\ContentNegotiation\ControllerPlugin\QueryParams',
            'bodyParams'                 => 'ZF\ContentNegotiation\ControllerPlugin\BodyParams',
        )
    )
);
