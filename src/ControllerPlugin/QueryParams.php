<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation\ControllerPlugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Mvc\Controller\AbstractController;
use ZF\ContentNegotiation\ParameterDataContainer;

class QueryParams extends AbstractPlugin
{
    public function __invoke()
    {
        $controller = $this->getController();
        if ($controller instanceof AbstractController) {
            $parameterData = $controller->getEvent()->getParam('ZFContentNegotiationParameterData');
            if ($parameterData instanceof ParameterDataContainer) {
                return $parameterData->getQueryParams();
            }
        }

        return $this->getController()->getRequest()->getQuery()->toArray();
    }
}
