<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation;

use Zend\Mime\Decode;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

class ContentTypeListener
{
    protected $jsonErrors = array(
        JSON_ERROR_DEPTH          => 'Maximum stack depth exceeded',
        JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
        JSON_ERROR_CTRL_CHAR      => 'Unexpected control character found',
        JSON_ERROR_SYNTAX         => 'Syntax error, malformed JSON',
        JSON_ERROR_UTF8           => 'Malformed UTF-8 characters, possibly incorrectly encoded',
    );

    /**
     * Perform content negotiation
     *
     * For HTTP methods expecting body content, attempts to match the incoming
     * content-type against the list of allowed content types, and then performs
     * appropriate content deserialization.
     *
     * If an error occurs during deserialization, an ApiProblemResponse is
     * returned, indicating an issue with the submission.
     *
     * @param MvcEvent $e
     * @return null|ApiProblemResponse
     */
    public function __invoke(MvcEvent $e)
    {
        $request       = $e->getRequest();
        if (!method_exists($request, 'getHeaders')) {
            // Not an HTTP request; nothing to do
            return;
        }

        $routeMatch    = $e->getRouteMatch();
        $parameterData = new ParameterDataContainer();

        // route parameters:
        $routeParams = $routeMatch->getParams();
        $parameterData->setRouteParams($routeParams);

        // query parameters:
        $parameterData->setQueryParams($request->getQuery()->toArray());

        // body parameters:
        $bodyParams  = array();
        $contentType = $request->getHeader('Content-type');
        switch ($request->getMethod()) {
            case $request::METHOD_POST:
                if ($contentType && $contentType->match('application/json')) {
                    $bodyParams = $this->decodeJson($request->getContent());
                    break;
                }

                $bodyParams = $request->getPost()->toArray();
                break;
            case $request::METHOD_PATCH:
            case $request::METHOD_PUT:
                $content = $request->getContent();

                if ($contentType && $contentType->match('multipart/form-data')) {
                    $bodyParams = $this->parseMultipartContent($content, $contentType, $request, $e);
                    break;
                }

                if ($contentType && $contentType->match('application/json')) {
                    $bodyParams = $this->decodeJson($content);
                    break;
                }

                // Stolen from AbstractRestfulController
                parse_str($content, $bodyParams);
                if (!is_array($bodyParams)
                    || (1 == count($bodyParams) && isset($bodyParams[0]))
                ) {
                    $bodyParams = $content;
                }
                break;
            default:
                break;
        }

        if ($bodyParams instanceof ApiProblemResponse) {
            return $bodyParams;
        }

        $parameterData->setBodyParams($bodyParams);
        $e->setParam('ZFContentNegotiationParameterData', $parameterData);
    }

    /**
     * Remove upload files if still present in filesystem
     *
     * @param MvcEvent $e
     */
    public function onFinish(MvcEvent $e)
    {
        $request      = $e->getRequest();
        $uploadTmpDir = $this->getUploadTempDir();

        foreach ($request->getFiles() as $name => $fileInfo) {
            $tmpDir  = dirname($fileInfo['tmp_name']);
            if (dirname($fileInfo['tmp_name']) !== $uploadTmpDir) {
                // File was moved
                continue;
            }

            if (! preg_match('/^zfc/', basename($fileInfo['tmp_name']))) {
                // File was moved
                continue;
            }

            if (! file_exists($fileInfo['tmp_name'])) {
                continue;
            }

            unlink($fileInfo['tmp_name']);
        }
    }

    /**
     * Attempt to decode a JSON string
     *
     * Decodes a JSON string and returns it; if invalid, returns
     * an ApiProblemResponse.
     *
     * @param string $json
     * @return mixed|ApiProblemResponse
     */
    public function decodeJson($json)
    {
        $data = json_decode($json, true);
        if (null !== $data) {
            return $data;
        }
        $error = json_last_error();
        if ($error === JSON_ERROR_NONE) {
            return $data;
        }

        $message = array_key_exists($error, $this->jsonErrors) ? $this->jsonErrors[$error] : 'Unknown error';
        return new ApiProblemResponse(
            new ApiProblem(400, sprintf('JSON decoding error: %s', $message))
        );
    }

    /**
     * Retrieve the upload temporary directory
     *
     * Queries for the INI upload_tmp_dir setting first, and returns that
     * value if set and a valid directory; otherwise, returns the
     * system_temp_dir().
     *
     * @return string
     */
    public function getUploadTempDir()
    {
        $tmpDir = ini_get('upload_tmp_dir');
        if (! empty($tmpDir) && is_dir($tmpDir)) {
            return $tmpDir;
        }

        return sys_get_temp_dir();
    }

    /**
     * Parse multipart/form-data content
     *
     * Splits parts into MIME parts, and iterates over each.
     *
     * If the part represents a variable (no "filename" indicated in the
     * Content-Disposition), then the value is added to the set of body
     * parameters.
     *
     * If a part represents a file, we pass it to uploadFile() and add the
     * entry to a parameter container of files; if any files were present at
     * all, the files parameter container is injected into the request.
     *
     * If no boundary is present in the request's Content-Type header, then the
     * method raises an exception.
     *
     * @param string $content
     * @param \Zend\Http\Header\HeaderInterface $contentType
     * @param \Zend\Http\Request $request
     * @param MvcEvent $event
     * @return array
     * @throws Exception\InvalidMultipartContentException
     */
    protected function parseMultipartContent($content, $contentType, $request, MvcEvent $event)
    {
        if (! preg_match('/boundary=(?P<boundary>[^\s]+)/', $contentType->getFieldValue(), $matches)) {
            throw new Exception\InvalidMultipartContentException();
        }

        $boundary = $matches['boundary'];
        $data     = new Parameters();
        $files    = new Parameters();
        foreach (Decode::splitMessageStruct($content, $boundary) as $part) {
            $this->parseMimePart($part, $data, $files);
        }

        if ($files->count()) {
            $request->setFiles($files);
            $this->attachFileCleanupListener($event);
        }

        return $data->toArray();
    }

    /**
     * Parse a single MIME part
     *
     * Extracts either form data or files, depending on the Content-Disposition
     * of the MIME part, injecting the appropriate container ($data or $files).
     *
     * @param array $part
     * @param Parameters $data
     * @param Parameters $files
     */
    protected function parseMimePart(array $part, Parameters $data, Parameters $files)
    {
        $headers = $part['header'];
        if (! $headers->has('Content-Disposition')) {
            return;
        }

        $disposition = $headers->get('Content-Disposition')->getFieldValue();
        if (! preg_match('/(?:;|\s)name="(?P<name>[^"]+)"/', $disposition, $matches)) {
            // unnamed parameter; move along...
            return;
        }
        $name = $matches['name'];

        if (preg_match('/filename="(?P<filename>[^"]*)"/', $disposition, $matches)) {
            $files->set($name, $this->uploadFile(
                $matches['filename'],
                $headers->has('Content-Type') ? $headers->get('Content-Type')->getFieldValue() : 'application/octet-stream',
                $part['body']));
            return;
        }

        $data->set($name, rtrim($part['body'], "\r\n"));
    }

    /**
     * Mimic PHP's file upload functionality
     *
     * This mimics PHP's file upload functionality by taking the provided
     * content and attempting to write it to a temporary file in either
     * the upload_tmp_dir or * system tmp directory.
     *
     * Returns a struct in the same format as used for elements of the
     * $_FILES array.
     *
     * @todo At some point, we likely need to support using a stream for file
     *       uploads in order to prevent memory issues.
     * @param string $filename
     * @param string $contentType
     * @param string $content
     * @return array
     */
    protected function uploadFile($filename, $contentType, $content)
    {
        $tmpDir = $this->getUploadTempDir();
        $file   = array(
            'name' => $filename,
            'type' => $contentType,
        );

        if (empty($tmpDir)) {
            $file['error'] = UPLOAD_ERR_NO_TMP_DIR;
            return $file;
        }

        $tmpFile = tempnam($tmpDir, 'zfc');
        $file['tmp_name'] = $tmpFile;

        if (false === file_put_contents($tmpFile, rtrim($content, "\r\n"))) {
            $file['error'] = UPLOAD_ERR_CANT_WRITE;
            return $file;
        }

        $file['error'] = UPLOAD_ERR_OK;
        $file['size']  = filesize($tmpFile);
        return $file;
    }

    /**
     * Attach the file cleanup listener
     *
     * @param MvcEvent $event
     */
    protected function attachFileCleanupListener(MvcEvent $event)
    {
        $target = $event->getTarget();
        if (! $target || ! is_object($target) || ! method_exists($target, 'getEventManager')) {
            return;
        }

        $events = $target->getEventManager();
        $events->attach('finish', array($this, 'onFinish'), 1000);
    }
}
