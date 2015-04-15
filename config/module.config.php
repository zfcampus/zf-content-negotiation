<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

return array(
    'filters' => array(
        'aliases'   => array(
            'Zend\Filter\File\RenameUpload' => 'filerenameupload',
        ),
        'factories' => array(
            'filerenameupload' => 'ZF\ContentNegotiation\Factory\RenameUploadFilterFactory',
        ),
    ),

    'validators' => array(
        'aliases'   => array(
            'Zend\Validator\File\UploadFile' => 'fileuploadfile',
        ),
        'factories' => array(
            'fileuploadfile' => 'ZF\ContentNegotiation\Factory\UploadFileValidatorFactory',
        ),
    ),

    'service_manager' => array(
        'invokables' => array(
            'ZF\ContentNegotiation\ContentTypeListener' => 'ZF\ContentNegotiation\ContentTypeListener',
        ),
        'factories' => array(
            'Request'                                         => 'ZF\ContentNegotiation\Factory\RequestFactory',
            'ZF\ContentNegotiation\AcceptListener'            => 'ZF\ContentNegotiation\Factory\AcceptListenerFactory',
            'ZF\ContentNegotiation\AcceptFilterListener'      => 'ZF\ContentNegotiation\Factory\AcceptFilterListenerFactory',
            'ZF\ContentNegotiation\ContentTypeFilterListener' => 'ZF\ContentNegotiation\Factory\ContentTypeFilterListenerFactory',
        )
    ),

    'zf-content-negotiation' => array(
        // This is an array of controller service names pointing to one of:
        // - a named selector (see below)
        // - an array of specific selectors, in the same format as for the
        //   selectors key
        'controllers' => array(),

        // This is an array of named selectors. Each selector consists of a
        // view model type pointing to the Accept mediatypes that will trigger
        // selection of that view model; see the documentation on the
        // AcceptableViewModelSelector plugin for details on the format:
        // http://zf2.readthedocs.org/en/latest/modules/zend.mvc.plugins.html?highlight=acceptableviewmodelselector#acceptableviewmodelselector-plugin
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
