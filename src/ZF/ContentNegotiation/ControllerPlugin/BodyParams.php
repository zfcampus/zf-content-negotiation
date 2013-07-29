<?php

namespace ZF\ContentNegotiation\ControllerPlugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Mvc\Controller\AbstractController;
use ZF\ContentNegotiation\ParameterDataContainer;

class BodyParams extends AbstractPlugin
{
    /**
     * Grabs a param from body match after content-negotation
     *
     * @param string $param
     * @param mixed $default
     * @return mixed
     */
    public function __invoke()
    {
        $controller = $this->getController();
        if ($controller instanceof AbstractController) {
            $parameterData = $controller->getEvent()->getParam('ZFContentNegotiationParameterData');
            if ($parameterData instanceof ParameterDataContainer) {
                return $parameterData->getBodyParams();
            }
        }

        return $controller->getRequest()->getPost();
    }
}