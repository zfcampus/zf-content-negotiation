<?php
return array(
    'controllers' => array(
        'initializers' => array(
            'ZF\ContentNegotiation\ControllerInitializer'
        )
    ),
    'controller_plugins' => array(
        'invokables' => array(
            'routeParam' => 'ZF\ContentNegotiation\ControllerPlugin\RouteParam',
            'queryParam' => 'ZF\ContentNegotiation\ControllerPlugin\QueryParam',
            'bodyParam' => 'ZF\ContentNegotiation\ControllerPlugin\BodyParam',
            'routeParams' => 'ZF\ContentNegotiation\ControllerPlugin\RouteParams',
            'queryParams' => 'ZF\ContentNegotiation\ControllerPlugin\QueryParams',
            'bodyParams' => 'ZF\ContentNegotiation\ControllerPlugin\BodyParams',
        )
    )
);
