<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\ContentNegotiation;

use Zend\Http\Header\ContentType as ContentTypeHeader;
use Zend\Http\Request as HttpRequest;
use Zend\Mime\Decode;
use Zend\Stdlib\Parameters;

class MultipartContentParser
{
    protected $boundary;

    protected $request;

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
            return $this->parseFromStream();
        }

        return $this->parseContent();
    }

    /**
     * Parse upload content from a content stream
     * 
     * @return array
     */
    protected function parseFromStream()
    {
        $stream = $this->request->getContentAsStream();

        $data           = new Parameters();
        $files          = new Parameters();
        $partInProgress = false;
        $inHeader       = false;
        $inBody         = false;
        $headers        = array();
        $header         = '';
        $headerValue    = '';
        $name           = false;
        $content        = '';
        $filename       = false;
        $mimeType       = false;
        $tmpFile        = false;

        $partBoundaryPatternStart = '/^--' . $this->boundary . '$/';
        $partBoundaryPatternEnd   = '/^--' . $this->boundary . '--$/';

        while (false !== ($line = fgets($stream))) {
            if (preg_match($partBoundaryPatternEnd, rtrim($line))) {
                // Met the "end" boundary; time to stop!
                break;
            }

            if (preg_match($partBoundaryPatternStart, rtrim($line))) {
                if (! $partInProgress) {
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

                // New part to parse!
                $partInProgress = true;
                $inHeader       = true;
                $inBody         = false;
                $headers        = array();
                $header         = '';
                $headerValue    = '';
                continue;
            }

            if (! $partInProgress) {
                // We're not inside a part, so do nothing.
                continue;
            }

            if ($inHeader) {
                if (empty($line)) {
                    // Headers are done; cleanup
                    $headers[$header] = $headerValue;
                    $inHeader = false;
                    $inBody   = true;
                    $content  = '';
                    $file     = array();
                    $tmpFile  = false;
                    $lastline = null;

                    // Parse headers
                    $name = $this->getNameFromHeaders($headers);

                    if (! $name) {
                        throw new Exception\InvalidMultipartContentException('Missing Content-Disposition header, or Content-Disposition header does not contain a "name" field');
                    }

                    $filename = $this->getFilenameFromHeaders($headers);
                    $mimeType = $this->getMimeTypeFromHeaders($headers);
                    continue;
                }

                if (preg_match('/^(?P<header>[a-z]+[a-z0-9_-]+):\s*(?P<value>.*)$/i', rtrim($line), $matches)) {
                    if (! empty($header)) {
                        $headers[strtoupper($header)] = $headerValue;
                        $header = $matches['header'];
                        $headerValue = $matches['value'];
                    }

                    continue;
                }

                if (! $header) {
                    throw new Exception\InvalidMultipartContentException('Malformed or missing MIME part header for multipart content');
                }

                $headerValue .= rtrim($line);
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
     * Parse content already fetched (i.e., not a stream) for data/files
     * 
     * @return array
     */
    protected function parseContent()
    {
        $data     = new Parameters();
        $files    = new Parameters();
        foreach (Decode::splitMessageStruct($this->request->getContent(), $this->boundary) as $part) {
            $this->parseMimePart($part, $data, $files);
        }

        if ($files->count()) {
            $this->request->setFiles($files);
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

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $type  = finfo_file($finfo, $tmpFile);
            if (false !== $type) {
                $file['type'] = $type;
            }
        }

        return $file;
    }

    /**
     * Retrieve the part name from the content disposition, if present
     * 
     * @param array $headers 
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
     * @param array $headers 
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
     * @param array $headers 
     * @return array
     */
    protected function getMimeTypeFromHeaders(array $headers)
    {
        if (! isset($headers['CONTENT-TYPE'])) {
            return 'application/octet-stream';
        }

        return $headers['CONTENT-TYPE'];
    }
}
