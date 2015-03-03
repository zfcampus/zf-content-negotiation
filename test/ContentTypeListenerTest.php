<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\ContentNegotiation;

use PHPUnit_Framework_TestCase as TestCase;
use ReflectionObject;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\Stdlib\Parameters;
use ZF\ContentNegotiation\ContentTypeListener;
use ZF\ContentNegotiation\MultipartContentParser;
use ZF\ContentNegotiation\Request as ContentNegotiationRequest;

class ContentTypeListenerTest extends TestCase
{
    public function setUp()
    {
        $this->listener = new ContentTypeListener();
    }

    public function methodsWithBodies()
    {
        return array(
            'post' => array('POST'),
            'patch' => array('PATCH'),
            'put' => array('PUT'),
            'delete' => array('DELETE'),
        );
    }

    /**
     * @group 3
     * @dataProvider methodsWithBodies
     */
    public function testJsonDecodeErrorsReturnsProblemResponse($method)
    {
        $listener = $this->listener;

        $request = new Request();
        $request->setMethod($method);
        $request->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        $request->setContent('Invalid JSON data');

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setRouteMatch(new RouteMatch(array()));

        $result = $listener($event);
        $this->assertInstanceOf('ZF\ApiProblem\ApiProblemResponse', $result);
        $problem = $result->getApiProblem();
        $this->assertEquals(400, $problem->status);
        $this->assertContains('JSON decoding', $problem->detail);
    }

    public function multipartFormDataMethods()
    {
        return array(
            'patch'  => array('patch'),
            'put'    => array('put'),
            'delete' => array('delete'),
        );
    }

    /**
     * @dataProvider multipartFormDataMethods
     */
    public function testCanDecodeMultipartFormDataRequestsForPutPatchAndDeleteOperations($method)
    {
        $request = new Request();
        $request->setMethod($method);
        $request->getHeaders()->addHeaderLine(
            'Content-Type',
            'multipart/form-data; boundary=6603ddd555b044dc9a022f3ad9281c20'
        );
        $request->setContent(file_get_contents(__DIR__ . '/TestAsset/multipart-form-data.txt'));

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setRouteMatch(new RouteMatch(array()));

        $listener = $this->listener;
        $result = $listener($event);

        $parameterData = $event->getParam('ZFContentNegotiationParameterData');
        $params = $parameterData->getBodyParams();
        $this->assertEquals(array(
            'mime_type' => 'md',
        ), $params);

        $files = $request->getFiles();
        $this->assertEquals(1, $files->count());
        $file = $files->get('text');
        $this->assertInternalType('array', $file);
        $this->assertArrayHasKey('error', $file);
        $this->assertArrayHasKey('name', $file);
        $this->assertArrayHasKey('tmp_name', $file);
        $this->assertArrayHasKey('size', $file);
        $this->assertArrayHasKey('type', $file);
        $this->assertEquals('README.md', $file['name']);
        $this->assertRegexp('/^zfc/', basename($file['tmp_name']));
        $this->assertTrue(file_exists($file['tmp_name']));
    }

    /**
     * @dataProvider multipartFormDataMethods
     */
    public function testCanDecodeMultipartFormDataRequestsFromStreamsForPutAndPatchOperations($method)
    {
        $request = new ContentNegotiationRequest();
        $request->setMethod($method);
        $request->getHeaders()->addHeaderLine(
            'Content-Type',
            'multipart/form-data; boundary=6603ddd555b044dc9a022f3ad9281c20'
        );
        $request->setContentStream('file://' . realpath(__DIR__ . '/TestAsset/multipart-form-data.txt'));

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setRouteMatch(new RouteMatch(array()));

        $listener = $this->listener;
        $result = $listener($event);

        $parameterData = $event->getParam('ZFContentNegotiationParameterData');
        $params = $parameterData->getBodyParams();
        $this->assertEquals(array(
            'mime_type' => 'md',
        ), $params);

        $files = $request->getFiles();
        $this->assertEquals(1, $files->count());
        $file = $files->get('text');
        $this->assertInternalType('array', $file);
        $this->assertArrayHasKey('error', $file);
        $this->assertArrayHasKey('name', $file);
        $this->assertArrayHasKey('tmp_name', $file);
        $this->assertArrayHasKey('size', $file);
        $this->assertArrayHasKey('type', $file);
        $this->assertEquals('README.md', $file['name']);
        $this->assertRegexp('/^zfc/', basename($file['tmp_name']));
        $this->assertTrue(file_exists($file['tmp_name']));
    }

    public function testDecodingMultipartFormDataWithFileRegistersFileCleanupEventListener()
    {
        $request = new Request();
        $request->setMethod('PATCH');
        $request->getHeaders()->addHeaderLine(
            'Content-Type',
            'multipart/form-data; boundary=6603ddd555b044dc9a022f3ad9281c20'
        );
        $request->setContent(file_get_contents(__DIR__ . '/TestAsset/multipart-form-data.txt'));

        $target = new TestAsset\EventTarget();
        $events = $this->getMock('Zend\EventManager\EventManagerInterface');
        $events->expects($this->once())
            ->method('attach')
            ->with(
                $this->equalTo('finish'),
                $this->equalTo(array($this->listener, 'onFinish')),
                $this->equalTo(1000)
            );
        $target->events = $events;

        $event = new MvcEvent();
        $event->setTarget($target);
        $event->setRequest($request);
        $event->setRouteMatch(new RouteMatch(array()));

        $listener = $this->listener;
        $result = $listener($event);
    }

    public function testOnFinishWillRemoveAnyUploadFilesUploadedByTheListener()
    {
        $tmpDir  = MultipartContentParser::getUploadTempDir();
        $tmpFile = tempnam($tmpDir, 'zfc');
        file_put_contents($tmpFile, 'File created by ' . __CLASS__);

        $files = new Parameters(array(
            'test' => array(
                'error'    => UPLOAD_ERR_OK,
                'name'     => 'test.txt',
                'type'     => 'text/plain',
                'tmp_name' => $tmpFile,
                'size'     => filesize($tmpFile),
            ),
        ));
        $request = new Request();
        $request->setFiles($files);

        $event = new MvcEvent();
        $event->setRequest($request);

        $r = new ReflectionObject($this->listener);
        $p = $r->getProperty('uploadTmpDir');
        $p->setAccessible(true);
        $p->setValue($this->listener, $tmpDir);

        $this->listener->onFinish($event);
        $this->assertFalse(file_exists($tmpFile));
    }

    public function testOnFinishDoesNotRemoveUploadFilesTheListenerDidNotCreate()
    {
        $tmpDir  = MultipartContentParser::getUploadTempDir();
        $tmpFile = tempnam($tmpDir, 'php');
        file_put_contents($tmpFile, 'File created by ' . __CLASS__);

        $files = new Parameters(array(
            'test' => array(
                'error'    => UPLOAD_ERR_OK,
                'name'     => 'test.txt',
                'type'     => 'text/plain',
                'tmp_name' => $tmpFile,
                'size'     => filesize($tmpFile),
            ),
        ));
        $request = new Request();
        $request->setFiles($files);

        $event = new MvcEvent();
        $event->setRequest($request);

        $this->listener->onFinish($event);
        $this->assertTrue(file_exists($tmpFile));
        unlink($tmpFile);
    }

    public function testOnFinishDoesNotRemoveUploadFilesThatHaveBeenMoved()
    {
        $tmpDir  = sys_get_temp_dir() . '/' . str_replace('\\', '_', __CLASS__);
        mkdir($tmpDir);
        $tmpFile = tempnam($tmpDir, 'zfc');

        $files = new Parameters(array(
            'test' => array(
                'error'    => UPLOAD_ERR_OK,
                'name'     => 'test.txt',
                'type'     => 'text/plain',
                'tmp_name' => $tmpFile,
            ),
        ));
        $request = new Request();
        $request->setFiles($files);

        $event = new MvcEvent();
        $event->setRequest($request);

        $this->listener->onFinish($event);
        $this->assertTrue(file_exists($tmpFile));
        unlink($tmpFile);
        rmdir($tmpDir);
    }
}
