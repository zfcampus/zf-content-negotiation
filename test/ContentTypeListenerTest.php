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
        return [
            'post' => ['POST'],
            'patch' => ['PATCH'],
            'put' => ['PUT'],
            'delete' => ['DELETE'],
        ];
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
        $event->setRouteMatch(new RouteMatch([]));

        $result = $listener($event);
        $this->assertInstanceOf('ZF\ApiProblem\ApiProblemResponse', $result);
        $problem = $result->getApiProblem();
        $this->assertEquals(400, $problem->status);
        $this->assertContains('JSON decoding', $problem->detail);
    }

    public function multipartFormDataMethods()
    {
        return [
            'patch'  => ['patch'],
            'put'    => ['put'],
            'delete' => ['delete'],
        ];
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
        $event->setRouteMatch(new RouteMatch([]));

        $listener = $this->listener;
        $result = $listener($event);

        $parameterData = $event->getParam('ZFContentNegotiationParameterData');
        $params = $parameterData->getBodyParams();
        $this->assertEquals([
            'mime_type' => 'md',
        ], $params);

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
        $event->setRouteMatch(new RouteMatch([]));

        $listener = $this->listener;
        $result = $listener($event);

        $parameterData = $event->getParam('ZFContentNegotiationParameterData');
        $params = $parameterData->getBodyParams();
        $this->assertEquals([
            'mime_type' => 'md',
        ], $params);

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
                $this->equalTo([$this->listener, 'onFinish']),
                $this->equalTo(1000)
            );
        $target->events = $events;

        $event = new MvcEvent();
        $event->setTarget($target);
        $event->setRequest($request);
        $event->setRouteMatch(new RouteMatch([]));

        $listener = $this->listener;
        $result = $listener($event);
    }

    public function testOnFinishWillRemoveAnyUploadFilesUploadedByTheListener()
    {
        $tmpDir  = MultipartContentParser::getUploadTempDir();
        $tmpFile = tempnam($tmpDir, 'zfc');
        file_put_contents($tmpFile, 'File created by ' . __CLASS__);

        $files = new Parameters([
            'test' => [
                'error'    => UPLOAD_ERR_OK,
                'name'     => 'test.txt',
                'type'     => 'text/plain',
                'tmp_name' => $tmpFile,
                'size'     => filesize($tmpFile),
            ],
        ]);
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

        $files = new Parameters([
            'test' => [
                'error'    => UPLOAD_ERR_OK,
                'name'     => 'test.txt',
                'type'     => 'text/plain',
                'tmp_name' => $tmpFile,
                'size'     => filesize($tmpFile),
            ],
        ]);
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

        $files = new Parameters([
            'test' => [
                'error'    => UPLOAD_ERR_OK,
                'name'     => 'test.txt',
                'type'     => 'text/plain',
                'tmp_name' => $tmpFile,
            ],
        ]);
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
        $event->setRouteMatch(new RouteMatch([]));

        $result = $listener($event);
        $this->assertNull($result);
        $params = $event->getParam('ZFContentNegotiationParameterData');
        $this->assertEquals([], $params->getBodyParams());
    }

    public function methodsWithBlankBodies()
    {
        return [
            'post-space'             => ['POST', ' '],
            'post-lines'             => ['POST', "\n\n"],
            'post-lines-and-space'   => ['POST', "  \n  \n"],
            'patch-space'            => ['PATCH', ' '],
            'patch-lines'            => ['PATCH', "\n\n"],
            'patch-lines-and-space'  => ['PATCH', "  \n  \n"],
            'put-space'              => ['PUT', ' '],
            'put-lines'              => ['PUT', "\n\n"],
            'put-lines-and-space'    => ['PUT', "  \n  \n"],
            'delete-space'           => ['DELETE', ' '],
            'delete-lines'           => ['DELETE', "\n\n"],
            'delete-lines-and-space' => ['DELETE', "  \n  \n"],
        ];
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
        $event->setRouteMatch(new RouteMatch([]));

        $result = $listener($event);
        $this->assertNull($result);
        $params = $event->getParam('ZFContentNegotiationParameterData');
        $this->assertEquals([], $params->getBodyParams());
    }

    public function methodsWithLeadingWhitespace()
    {
        return [
            'post-space'             => ['POST', ' {"foo": "bar"}'],
            'post-lines'             => ['POST', "\n\n{\"foo\": \"bar\"}"],
            'post-lines-and-space'   => ['POST', "  \n  \n{\"foo\": \"bar\"}"],
            'patch-space'             => ['PATCH', ' {"foo": "bar"}'],
            'patch-lines'             => ['PATCH', "\n\n{\"foo\": \"bar\"}"],
            'patch-lines-and-space'   => ['PATCH', "  \n  \n{\"foo\": \"bar\"}"],
            'put-space'             => ['PUT', ' {"foo": "bar"}'],
            'put-lines'             => ['PUT', "\n\n{\"foo\": \"bar\"}"],
            'put-lines-and-space'   => ['PUT', "  \n  \n{\"foo\": \"bar\"}"],
            'delete-space'             => ['DELETE', ' {"foo": "bar"}'],
            'delete-lines'             => ['DELETE', "\n\n{\"foo\": \"bar\"}"],
            'delete-lines-and-space'   => ['DELETE', "  \n  \n{\"foo\": \"bar\"}"],
        ];
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
        $event->setRouteMatch(new RouteMatch([]));

        $result = $listener($event);
        $this->assertNull($result);
        $params = $event->getParam('ZFContentNegotiationParameterData');
        $this->assertEquals(['foo' => 'bar'], $params->getBodyParams());
    }

    public function methodsWithTrailingWhitespace()
    {
        return [
            'post-space'             => ['POST', '{"foo": "bar"} '],
            'post-lines'             => ['POST', "{\"foo\": \"bar\"}\n\n"],
            'post-lines-and-space'   => ['POST', "{\"foo\": \"bar\"}  \n  \n"],
            'patch-space'             => ['PATCH', '{"foo": "bar"} '],
            'patch-lines'             => ['PATCH', "{\"foo\": \"bar\"}\n\n"],
            'patch-lines-and-space'   => ['PATCH', "{\"foo\": \"bar\"}  \n  \n"],
            'put-space'             => ['PUT', '{"foo": "bar"} '],
            'put-lines'             => ['PUT', "{\"foo\": \"bar\"}\n\n"],
            'put-lines-and-space'   => ['PUT', "{\"foo\": \"bar\"}  \n  \n"],
            'delete-space'             => ['DELETE', '{"foo": "bar"} '],
            'delete-lines'             => ['DELETE', "{\"foo\": \"bar\"}\n\n"],
            'delete-lines-and-space'   => ['DELETE', "{\"foo\": \"bar\"}  \n  \n"],
        ];
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
        $event->setRouteMatch(new RouteMatch([]));

        $result = $listener($event);
        $this->assertNull($result);
        $params = $event->getParam('ZFContentNegotiationParameterData');
        $this->assertEquals(['foo' => 'bar'], $params->getBodyParams());
    }

    public function methodsWithLeadingAndTrailingWhitespace()
    {
        return [
            'post-space'             => ['POST', ' {"foo": "bar"} '],
            'post-lines'             => ['POST', "\n\n{\"foo\": \"bar\"}\n\n"],
            'post-lines-and-space'   => ['POST', "  \n  \n{\"foo\": \"bar\"}  \n  \n"],
            'patch-space'             => ['PATCH', ' {"foo": "bar"} '],
            'patch-lines'             => ['PATCH', "\n\n{\"foo\": \"bar\"}\n\n"],
            'patch-lines-and-space'   => ['PATCH', "  \n  \n{\"foo\": \"bar\"}  \n  \n"],
            'put-space'             => ['PUT', ' {"foo": "bar"} '],
            'put-lines'             => ['PUT', "\n\n{\"foo\": \"bar\"}\n\n"],
            'put-lines-and-space'   => ['PUT', "  \n  \n{\"foo\": \"bar\"}  \n  \n"],
            'delete-space'             => ['DELETE', ' {"foo": "bar"} '],
            'delete-lines'             => ['DELETE', "\n\n{\"foo\": \"bar\"}\n\n"],
            'delete-lines-and-space'   => ['DELETE', "  \n  \n{\"foo\": \"bar\"}  \n  \n"],
        ];
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
        $event->setRouteMatch(new RouteMatch([]));

        $result = $listener($event);
        $this->assertNull($result);
        $params = $event->getParam('ZFContentNegotiationParameterData');
        $this->assertEquals(['foo' => 'bar'], $params->getBodyParams());
    }

    public function methodsWithWhitespaceInsideBody()
    {
        return [
            'post-space'             => ['POST', '{"foo": "bar foo"}'],
            'patch-space'             => ['PATCH', '{"foo": "bar foo"}'],
            'put-space'             => ['PUT', '{"foo": "bar foo"}'],
            'delete-space'             => ['DELETE', '{"foo": "bar foo"}'],
        ];
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
        $event->setRouteMatch(new RouteMatch([]));

        $result = $listener($event);
        $this->assertNull($result);
        $params = $event->getParam('ZFContentNegotiationParameterData');
        $this->assertEquals(['foo' => 'bar foo'], $params->getBodyParams());
    }

    /**
     * @group 42
     */
    public function testReturns400ResponseWhenBodyPartIsMissingName()
    {
        $request = new Request();
        $request->setMethod('PUT');
        $request->getHeaders()->addHeaderLine(
            'Content-Type',
            'multipart/form-data; boundary=6603ddd555b044dc9a022f3ad9281c20'
        );
        $request->setContent(file_get_contents(__DIR__ . '/TestAsset/multipart-form-data-missing-name.txt'));

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setRouteMatch(new RouteMatch([]));

        $listener = $this->listener;
        $result = $listener($event);

        $this->assertInstanceOf('ZF\ApiProblem\ApiProblemResponse', $result);
        $this->assertEquals(400, $result->getStatusCode());
        $details = $result->getApiProblem()->toArray();
        $this->assertContains('does not contain a "name" field', $details['detail']);
    }
}
