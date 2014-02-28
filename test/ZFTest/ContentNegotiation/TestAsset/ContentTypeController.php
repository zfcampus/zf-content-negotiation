<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\ContentNegotiation\TestAsset;

use Zend\Mvc\Controller\AbstractActionController;

class ContentTypeController extends AbstractActionController
{
    public function setRequest($request)
    {
        $this->request = $request;
    }
}
