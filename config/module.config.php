<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

return [
    'filters' => [
        'aliases'   => [
            'Zend\Filter\File\RenameUpload' => 'filerenameupload',
        ],
        'factories' => [
            'filerenameupload' => 'ZF\ContentNegotiation\Factory\RenameUploadFilterFactory',
        ],
    ],

    'validators' => [
        'aliases'   => [
            'Zend\Validator\File\UploadFile' => 'fileuploadfile',
        ],
        'factories' => [
            'fileuploadfile' => 'ZF\ContentNegotiation\Factory\UploadFileValidatorFactory',
        ],
    ],

    'service_manager' => [
        'invokables' => [
            'ZF\ContentNegotiation\ContentTypeListener' => 'ZF\ContentNegotiation\ContentTypeListener',
        ],
        'factories' => [
            'Request'                                         => 'ZF\ContentNegotiation\Factory\RequestFactory',
            'ZF\ContentNegotiation\AcceptListener'            => 'ZF\ContentNegotiation\Factory\AcceptListenerFactory',
            'ZF\ContentNegotiation\AcceptFilterListener'      => 'ZF\ContentNegotiation\Factory\AcceptFilterListenerFactory',
            'ZF\ContentNegotiation\ContentTypeFilterListener' => 'ZF\ContentNegotiation\Factory\ContentTypeFilterListenerFactory',
            'ZF\ContentNegotiation\ContentNegotiationOptions' => 'ZF\ContentNegotiation\Factory\ContentNegotiationOptionsFactory',
        ],
    ],

    'zf-content-negotiation' => [
        // This is an array of controller service names pointing to one of:
        // - a named selector (see below)
        // - an array of specific selectors, in the same format as for the
        //   selectors key
        'controllers' => [],

        // This is an array of named selectors. Each selector consists of a
        // view model type pointing to the Accept mediatypes that will trigger
        // selection of that view model; see the documentation on the
        // AcceptableViewModelSelector plugin for details on the format:
        // http://zf2.readthedocs.org/en/latest/modules/zend.mvc.plugins.html?highlight=acceptableviewmodelselector#acceptableviewmodelselector-plugin
        'selectors'   => [
            'Json' => [
                'ZF\ContentNegotiation\JsonModel' => [
                    'application/json',
                    'application/*+json',
                ],
            ],
        ],

        // Array of controller service name => allowed accept header pairs.
        // The allowed content type may be a string, or an array of strings.
        'accept_whitelist' => [],

        // Array of controller service name => allowed content type pairs.
        // The allowed content type may be a string, or an array of strings.
        'content_type_whitelist' => [],
    ],

    'controller_plugins' => [
        'invokables' => [
            'routeParam'  => 'ZF\ContentNegotiation\ControllerPlugin\RouteParam',
            'queryParam'  => 'ZF\ContentNegotiation\ControllerPlugin\QueryParam',
            'bodyParam'   => 'ZF\ContentNegotiation\ControllerPlugin\BodyParam',
            'routeParams' => 'ZF\ContentNegotiation\ControllerPlugin\RouteParams',
            'queryParams' => 'ZF\ContentNegotiation\ControllerPlugin\QueryParams',
            'bodyParams'  => 'ZF\ContentNegotiation\ControllerPlugin\BodyParams',
        ],
    ],
];
