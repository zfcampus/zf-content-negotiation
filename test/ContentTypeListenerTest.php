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

    /**
     * @group 35
     * @dataProvider methodsWithBodies
     */
    public function testWillNotAttemptToInjectNullValueForBodyParams($method)
    {
        $listener = $this->listener;

        $request = new Request();
        $request->setMethod($method);
        $request->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        $request->setContent('');

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setRouteMatch(new RouteMatch(array()));

        $result = $listener($event);
        $this->assertNull($result);
        $params = $event->getParam('ZFContentNegotiationParameterData');
        $this->assertEquals(array(), $params->getBodyParams());
    }

    public function methodsWithBlankBodies()
    {
        return array(
            'post-space'             => array('POST', ' '),
            'post-lines'             => array('POST', "\n\n"),
            'post-lines-and-space'   => array('POST', "  \n  \n"),
            'patch-space'            => array('PATCH', ' '),
            'patch-lines'            => array('PATCH', "\n\n"),
            'patch-lines-and-space'  => array('PATCH', "  \n  \n"),
            'put-space'              => array('PUT', ' '),
            'put-lines'              => array('PUT', "\n\n"),
            'put-lines-and-space'    => array('PUT', "  \n  \n"),
            'delete-space'           => array('DELETE', ' '),
            'delete-lines'           => array('DELETE', "\n\n"),
            'delete-lines-and-space' => array('DELETE', "  \n  \n"),
        );
    }

    /**
     * @group 36
     * @dataProvider methodsWithBlankBodies
     */
    public function testWillNotAttemptToInjectNullValueForBodyParamsWhenContentIsWhitespace($method, $content)
    {
        $listener = $this->listener;

        $request = new Request();
        $request->setMethod($method);
        $request->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        $request->setContent($content);

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setRouteMatch(new RouteMatch(array()));

        $result = $listener($event);
        $this->assertNull($result);
        $params = $event->getParam('ZFContentNegotiationParameterData');
        $this->assertEquals(array(), $params->getBodyParams());
    }

    public function methodsWithLeadingWhitespace()
    {
        return array(
            'post-space'             => array('POST', ' {"foo": "bar"}'),
            'post-lines'             => array('POST', "\n\n{\"foo\": \"bar\"}"),
            'post-lines-and-space'   => array('POST', "  \n  \n{\"foo\": \"bar\"}"),
            'patch-space'             => array('PATCH', ' {"foo": "bar"}'),
            'patch-lines'             => array('PATCH', "\n\n{\"foo\": \"bar\"}"),
            'patch-lines-and-space'   => array('PATCH', "  \n  \n{\"foo\": \"bar\"}"),
            'put-space'             => array('PUT', ' {"foo": "bar"}'),
            'put-lines'             => array('PUT', "\n\n{\"foo\": \"bar\"}"),
            'put-lines-and-space'   => array('PUT', "  \n  \n{\"foo\": \"bar\"}"),
            'delete-space'             => array('DELETE', ' {"foo": "bar"}'),
            'delete-lines'             => array('DELETE', "\n\n{\"foo\": \"bar\"}"),
            'delete-lines-and-space'   => array('DELETE', "  \n  \n{\"foo\": \"bar\"}"),
        );
    }

    /**
     * @group 36
     * @dataProvider methodsWithLeadingWhitespace
     */
    public function testWillHandleJsonContentWithLeadingWhitespace($method, $content)
    {
        $listener = $this->listener;

        $request = new Request();
        $request->setMethod($method);
        $request->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        $request->setContent($content);

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setRouteMatch(new RouteMatch(array()));

        $result = $listener($event);
        $this->assertNull($result);
        $params = $event->getParam('ZFContentNegotiationParameterData');
        $this->assertEquals(array('foo' => 'bar'), $params->getBodyParams());
    }

    public function methodsWithTrailingWhitespace()
    {
        return array(
            'post-space'             => array('POST', '{"foo": "bar"} '),
            'post-lines'             => array('POST', "{\"foo\": \"bar\"}\n\n"),
            'post-lines-and-space'   => array('POST', "{\"foo\": \"bar\"}  \n  \n"),
            'patch-space'             => array('PATCH', '{"foo": "bar"} '),
            'patch-lines'             => array('PATCH', "{\"foo\": \"bar\"}\n\n"),
            'patch-lines-and-space'   => array('PATCH', "{\"foo\": \"bar\"}  \n  \n"),
            'put-space'             => array('PUT', '{"foo": "bar"} '),
            'put-lines'             => array('PUT', "{\"foo\": \"bar\"}\n\n"),
            'put-lines-and-space'   => array('PUT', "{\"foo\": \"bar\"}  \n  \n"),
            'delete-space'             => array('DELETE', '{"foo": "bar"} '),
            'delete-lines'             => array('DELETE', "{\"foo\": \"bar\"}\n\n"),
            'delete-lines-and-space'   => array('DELETE', "{\"foo\": \"bar\"}  \n  \n"),
        );
    }

    /**
     * @group 36
     * @dataProvider methodsWithTrailingWhitespace
     */
    public function testWillHandleJsonContentWithTrailingWhitespace($method, $content)
    {
        $listener = $this->listener;

        $request = new Request();
        $request->setMethod($method);
        $request->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        $request->setContent($content);

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setRouteMatch(new RouteMatch(array()));

        $result = $listener($event);
        $this->assertNull($result);
        $params = $event->getParam('ZFContentNegotiationParameterData');
        $this->assertEquals(array('foo' => 'bar'), $params->getBodyParams());
    }

    public function methodsWithLeadingAndTrailingWhitespace()
    {
        return array(
            'post-space'             => array('POST', ' {"foo": "bar"} '),
            'post-lines'             => array('POST', "\n\n{\"foo\": \"bar\"}\n\n"),
            'post-lines-and-space'   => array('POST', "  \n  \n{\"foo\": \"bar\"}  \n  \n"),
            'patch-space'             => array('PATCH', ' {"foo": "bar"} '),
            'patch-lines'             => array('PATCH', "\n\n{\"foo\": \"bar\"}\n\n"),
            'patch-lines-and-space'   => array('PATCH', "  \n  \n{\"foo\": \"bar\"}  \n  \n"),
            'put-space'             => array('PUT', ' {"foo": "bar"} '),
            'put-lines'             => array('PUT', "\n\n{\"foo\": \"bar\"}\n\n"),
            'put-lines-and-space'   => array('PUT', "  \n  \n{\"foo\": \"bar\"}  \n  \n"),
            'delete-space'             => array('DELETE', ' {"foo": "bar"} '),
            'delete-lines'             => array('DELETE', "\n\n{\"foo\": \"bar\"}\n\n"),
            'delete-lines-and-space'   => array('DELETE', "  \n  \n{\"foo\": \"bar\"}  \n  \n"),
        );
    }

    /**
     * @group 36
     * @dataProvider methodsWithLeadingAndTrailingWhitespace
     */
    public function testWillHandleJsonContentWithLeadingAndTrailingWhitespace($method, $content)
    {
        $listener = $this->listener;

        $request = new Request();
        $request->setMethod($method);
        $request->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        $request->setContent($content);

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setRouteMatch(new RouteMatch(array()));

        $result = $listener($event);
        $this->assertNull($result);
        $params = $event->getParam('ZFContentNegotiationParameterData');
        $this->assertEquals(array('foo' => 'bar'), $params->getBodyParams());
    }

    public function methodsWithWhitespaceInsideBody()
    {
        return array(
            'post-space'             => array('POST', '{"foo": "bar foo"}'),
            'patch-space'             => array('PATCH', '{"foo": "bar foo"}'),
            'put-space'             => array('PUT', '{"foo": "bar foo"}'),
            'delete-space'             => array('DELETE', '{"foo": "bar foo"}'),
        );
    }

    /**
     * @dataProvider methodsWithWhitespaceInsideBody
     */
    public function testWillNotRemoveWhitespaceInsideBody($method, $content)
    {
        $listener = $this->listener;

        $request = new Request();
        $request->setMethod($method);
        $request->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        $request->setContent($content);

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setRouteMatch(new RouteMatch(array()));

        $result = $listener($event);
        $this->assertNull($result);
        $params = $event->getParam('ZFContentNegotiationParameterData');
        $this->assertEquals(array('foo' => 'bar foo'), $params->getBodyParams());
    }
}
