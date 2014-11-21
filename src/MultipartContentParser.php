<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation;

use Zend\Http\Header\ContentType as ContentTypeHeader;
use Zend\Http\Request as HttpRequest;
use Zend\Stdlib\Parameters;

class MultipartContentParser
{
    /**
     * MIME multipart boundary
     *
     * @var string
     */
    protected $boundary;

    /**
     * @var HttpRequest
     */
    protected $request;

    /**
     * @param  ContentTypeHeader $contentType
     * @param  HttpRequest $request
     * @throws Exception\InvalidMultipartContentException if unable to detect MIME boundary
     */
    public function __construct(ContentTypeHeader $contentType, HttpRequest $request)
    {
        if (! preg_match('/boundary=(?P<boundary>[^\s]+)/', $contentType->getFieldValue(), $matches)) {
            throw new Exception\InvalidMultipartContentException();
        }

        $this->boundary = $matches['boundary'];
        $this->request  = $request;
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
    public static function getUploadTempDir()
    {
        $tmpDir = ini_get('upload_tmp_dir');
        if (! empty($tmpDir) && is_dir($tmpDir)) {
            return $tmpDir;
        }

        return sys_get_temp_dir();
    }

    /**
     * Parse the incoming request body
     *
     * Returns any discovered data parameters.
     *
     * @return array
     */
    public function parse()
    {
        if ($this->request instanceof Request) {
            return $this->parseFromStream($this->request->getContentAsStream());
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $this->request->getContent());
        rewind($stream);

        return $this->parseFromStream($stream);
    }

    /**
     * Parse upload content from a content stream
     *
     * @param  resource $stream
     * @return array
     * @throws Exception\InvalidMultipartContentException
     */
    protected function parseFromStream($stream)
    {
        $data           = new Parameters();
        $files          = new Parameters();
        $partInProgress = false;
        $inHeader       = false;
        $headers        = array();
        $header         = false;
        $name           = false;
        $content        = '';
        $file           = array();
        $filename       = false;
        $mimeType       = false;
        $tmpFile        = false;

        $partBoundaryPatternStart = '/^--' . $this->boundary . '(--)?/';
        $partBoundaryPatternEnd   = '/^--' . $this->boundary . '--$/';

        while (false !== ($line = fgets($stream))) {
            $trimmedLine = rtrim($line);

            if (preg_match($partBoundaryPatternStart, $trimmedLine)) {
                if ($partInProgress) {
                    // Time to handle the data we've already parsed!
                    // Data
                    if (! $filename) {
                        $data->set($name, rtrim($content, "\r\n"));
                    }

                    // File (successful upload so far)
                    if ($filename && $tmpFile) {
                        // Write the last line, stripping the EOL characters
                        if (false === fwrite($tmpFile, rtrim($lastline, "\r\n"))) {
                            // Ooops! error writing the very last line!
                            $file['error'] = UPLOAD_ERR_CANT_WRITE;
                            fclose($tmpFile);
                        } else {
                            // Success! Let's try and guess the MIME type based on the file written
                            fclose($tmpFile);

                            if ($mimeType === 'application/octet-stream' && function_exists('finfo_open')) {
                                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                $type  = finfo_file($finfo, $file['tmp_name']);
                                if (false !== $type) {
                                    $file['type'] = $type;
                                }
                            }

                            // Finally, set the filesize
                            $file['size'] = filesize($file['tmp_name']);
                        }
                    }

                    if ($filename) {
                        // At this point, we can add the file entry, regardless of error condition
                        $files->set($name, $file);
                    }
                }

                // Is this a boundary end? If so, we're done
                if (preg_match($partBoundaryPatternEnd, $trimmedLine)) {
                    // Met the "end" boundary; time to stop!
                    break;
                }

                // New part to parse!
                $partInProgress = true;
                $inHeader       = true;
                $headers        = array();
                $header         = '';

                continue;
            }

            if (! $partInProgress) {
                // We're not inside a part, so do nothing.
                continue;
            }

            if ($inHeader) {
                if (preg_match('/^\s*$/s', $line)) {
                    // Headers are done; cleanup
                    $inHeader = false;
                    $content  = '';
                    $file     = array('error' => UPLOAD_ERR_OK);
                    $tmpFile  = false;
                    $lastline = null;


                    // Parse headers
                    $name = $this->getNameFromHeaders($headers);

                    if (! $name) {
                        throw new Exception\InvalidMultipartContentException(
                            'Missing Content-Disposition header, or Content-Disposition header does not '
                            . 'contain a "name" field'
                        );
                    }

                    $filename = $this->getFilenameFromHeaders($headers);
                    $mimeType = $this->getMimeTypeFromHeaders($headers);
                    continue;
                }

                if (preg_match('/^(?P<header>[a-z]+[a-z0-9_-]+):\s*(?P<value>.*)$/i', $trimmedLine, $matches)) {
                    $header = strtoupper($matches['header']);
                    $headers[$header] = $matches['value'];

                    continue;
                }

                if (! $header) {
                    throw new Exception\InvalidMultipartContentException(
                        'Malformed or missing MIME part header for multipart content'
                    );
                }

                $headers[$header] .= $trimmedLine;
                continue;
            }

            // In the body content...

            // Data only; aggregate.
            if (! $filename) {
                $content .= $line;
                continue;
            }

            // If we've had an error already with the upload, continue parsing
            // to the end of the MIME part
            if ($file['error'] !== UPLOAD_ERR_OK) {
                continue;
            }

            // Create a temporary file handle if we haven't already
            if (! $tmpFile) {
                // Sets the file entry
                $file['name'] = $filename;
                $file['type'] = $mimeType;

                $tmpDir = $this->getUploadTempDir();
                if (empty($tmpDir)) {
                    // Cannot ascertain temporary directory; this is an error
                    $file['error'] = UPLOAD_ERR_NO_TMP_DIR;
                    continue;
                }

                $file['tmp_name'] = tempnam($tmpDir, 'zfc');
                $tmpFile = fopen($file['tmp_name'], 'wb');
                if (false === $tmpFile) {
                    // Cannot open the temporary file for writing; this is an error
                    $file['error'] = UPLOAD_ERR_CANT_WRITE;
                    continue;
                }
            }

            // Off-by-one operation. Last line must be trimmed, so we'll write
            // the lines one iteration behind.
            if (null === $lastline) {
                $lastline = $line;
                continue;
            }

            if (false === fwrite($tmpFile, $lastline)) {
                $file['error'] = UPLOAD_ERR_CANT_WRITE;
                fclose($tmpFile);
                continue;
            }
            $lastline = $line;
        }

        fclose($stream);

        if ($files->count()) {
            $this->request->setFiles($files);
        }

        return $data->toArray();
    }

    /**
     * Retrieve the part name from the content disposition, if present
     *
     * @param  array $headers
     * @return false|string
     */
    protected function getNameFromHeaders(array $headers)
    {
        if (! isset($headers['CONTENT-DISPOSITION'])) {
            return false;
        }

        if (! preg_match('/(?:;|\s)name="(?P<name>[^"]+)"/', $headers['CONTENT-DISPOSITION'], $matches)) {
            return false;
        }

        return $matches['name'];
    }

    /**
     * Retrieve the filename from the content disposition, if present
     *
     * @param  array $headers
     * @return false|string
     */
    protected function getFilenameFromHeaders(array $headers)
    {
        if (! isset($headers['CONTENT-DISPOSITION'])) {
            return false;
        }

        if (! preg_match('/filename="(?P<filename>[^"]*)"/', $headers['CONTENT-DISPOSITION'], $matches)) {
            return false;
        }

        return $matches['filename'];
    }

    /**
     * Retrieve the MIME type of the MIME part
     *
     * @param  array $headers
     * @return string
     */
    protected function getMimeTypeFromHeaders(array $headers)
    {
        if (! isset($headers['CONTENT-TYPE'])) {
            return 'application/octet-stream';
        }

        return $headers['CONTENT-TYPE'];
    }
}
