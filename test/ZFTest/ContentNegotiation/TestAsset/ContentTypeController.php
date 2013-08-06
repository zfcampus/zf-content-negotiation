<?php

namespace ZFTest\ContentNegotiation\TestAsset;

use Zend\Mvc\Controller\AbstractActionController;

class ContentTypeController extends AbstractActionController
{
    public function setRequest($request)
    {
        $this->request = $request;
    }
}
