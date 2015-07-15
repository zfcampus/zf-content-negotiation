<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\ContentNegotiation;

use ArrayIterator;
use ArrayObject;
use PHPUnit_Framework_TestCase as TestCase;
use ZF\ContentNegotiation\JsonModel;
use ZF\Hal\Entity as HalEntity;
use ZF\Hal\Collection as HalCollection;

class JsonModelTest extends TestCase
{
    public function testSetVariables()
    {
        $jsonModel = new JsonModel(new TestAsset\ModelWithJson());
        $this->assertEquals('bar', $jsonModel->getVariable('foo'));
    }

    public function testJsonModelIsAlwaysTerminal()
    {
        $jsonModel = new JsonModel([]);
        $jsonModel->setTerminal(false);
        $this->assertTrue($jsonModel->terminate());
    }

    public function testWillPullHalEntityFromPayloadToSerialize()
    {
        $jsonModel = new JsonModel([
            'payload' => new HalEntity(['id' => 2, 'title' => 'Hello world'], 1),
        ]);
        $json = $jsonModel->serialize();
        $data = json_decode($json, true);
        $this->assertInternalType('array', $data);
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals(2, $data['id']);
        $this->assertArrayHasKey('title', $data);
        $this->assertEquals('Hello world', $data['title']);
    }

    public function testWillPullHalCollectionFromPayloadToSerialize()
    {
        $collection = [
            ['foo' => 'bar'],
            ['bar' => 'baz'],
            ['baz' => 'bat'],
        ];

        $jsonModel = new JsonModel([
            'payload' => new HalCollection($collection),
        ]);
        $json = $jsonModel->serialize();
        $data = json_decode($json, true);
        $this->assertEquals($collection, $data);
    }

    public function testWillRaiseExceptionIfErrorOccursEncodingJson()
    {
        if (version_compare(PHP_VERSION, '5.5.0', 'lt')) {
            $this->markTestSkipped('This test only runs on 5.5 and up');
        }

        // Provide data that cannot be serialized to JSON
        $data = ['foo' => pack('H*', 'c32e')];
        $jsonModel = new JsonModel($data);
        $this->setExpectedException('ZF\ContentNegotiation\Exception\InvalidJsonException');
        $jsonModel->serialize();
    }

    /**
     * @group 17
     */
    public function testCanSerializeTraversables()
    {
        $variables = [
            'some' => 'content',
            'nested' => new ArrayObject([
                'objects' => 'should also be serialized',
                'arbitrarily' => new ArrayIterator([
                    'as' => 'deep as you like',
                ]),
            ]),
        ];
        $iterator  = new ArrayIterator($variables);
        $jsonModel = new JsonModel($iterator);
        $json = $jsonModel->serialize();
        $data = json_decode($json, true);


        $expected = [
            'some' => 'content',
            'nested' => [
                'objects' => 'should also be serialized',
                'arbitrarily' => [
                    'as' => 'deep as you like',
                ],
            ],
        ];

        $this->assertEquals($expected, $data);
    }
}
