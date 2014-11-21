<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation\ControllerPlugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Mvc\Exception\RuntimeException;
use Zend\Mvc\InjectApplicationEventInterface;
use Zend\Mvc\Controller\AbstractController;
use ZF\ContentNegotiation\ParameterDataContainer;

class RouteParam extends AbstractPlugin
{
    /**
     * @param  null|string $param
     * @param  null|mixed $default
     * @return mixed
     */
    public function __invoke($param = null, $default = null)
    {
        $controller = $this->getController();

        if (!$controller instanceof InjectApplicationEventInterface) {
            throw new RuntimeException(
                'Controllers must implement Zend\Mvc\InjectApplicationEventInterface to use this plugin.'
            );
        }

        if ($controller instanceof AbstractController) {
            $parameterData = $controller->getEvent()->getParam('ZFContentNegotiationParameterData');
            if ($parameterData instanceof ParameterDataContainer) {
                return $parameterData->getRouteParam($param, $default);
            }
        }

        return $controller->getEvent()->getRouteMatch()->getParam($param, $default);
    }
}
