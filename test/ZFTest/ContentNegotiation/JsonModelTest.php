<?php

namespace ZFTest\ContentNegotiation;

use ZF\ContentNegotiation\JsonModel;

class JsonModelTest extends \PHPUnit_Framework_TestCase
{
    public function testSetVariables()
    {
        $jsonModel = new JsonModel(new TestAsset\ModelWithJson());
        $this->assertEquals('bar', $jsonModel->getVariable('foo'));
    }
}
 