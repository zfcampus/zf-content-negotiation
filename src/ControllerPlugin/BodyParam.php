<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation\ControllerPlugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Mvc\Controller\AbstractController;
use ZF\ContentNegotiation\ParameterDataContainer;

class BodyParam extends AbstractPlugin
{
    /**
     * Grabs a param from body match after content-negotiation
     *
     * @param  null|string $param
     * @param  null|mixed $default
     * @return mixed
     */
    public function __invoke($param = null, $default = null)
    {
        $controller = $this->getController();
        if ($controller instanceof AbstractController) {
            $parameterData = $controller->getEvent()->getParam('ZFContentNegotiationParameterData');
            if ($parameterData instanceof ParameterDataContainer) {
                return $parameterData->getBodyParam($param, $default);
            }
        }

        return $controller->getRequest()->getPost($param, $default);
    }
}
