<?php

declare(strict_types=1);

namespace RuSender\Resources;

use Psr\Http\Message\ResponseInterface;
use RuSender\Exceptions\UnexpectedResponseException;
use RuSender\Http\Request;
use RuSender\Http\Transport;
use RuSender\Support\Path;
use RuSender\Types\FileInfo;
use RuSender\Types\Page;

/**
 * Account files: campaign attachments.
 *
 * @phpstan-import-type RequestOptions from Transport
 *
 * @phpstan-type UploadFile array{fileName: string, content: string}|array{fileName: string, path: string}
 */
final class Files extends AbstractResource
{
    /**
     * Lists account files, newest first, searchable by name.
     * Scope: `files.read`.
     *
     * @param array{page?: int, limit?: int, search?: string} $params
     * @param RequestOptions                                   $options
     *
     * @return Page<FileInfo>
     */
    public function list(array $params = [], array $options = []): Page
    {
        return $this->page(Request::get('/v1/public/files', $params), $options, FileInfo::class);
    }

    /**
     * Uploads campaign attachments in one request: 1–20 files, 5 MB in total. A file is raw bytes in `content` or
     * a `path`; the server detects the type from the contents. The set is validated as a whole: if any file fails
     * validation, none is saved and ValidationException is thrown — the rejected file is in `details`. Pass the
     * returned ids in campaign `attachments`. Not retried on failure: a retry would create duplicate files.
     * Scope: `files.write`.
     *
     * @param list<UploadFile> $files
     * @param RequestOptions   $options
     *
     * @return list<FileInfo>
     */
    public function upload(array $files, array $options = []): array
    {
        $parts = [];
        foreach ($files as $file) {
            if (isset($file['path'])) {
                $contents = @file_get_contents($file['path']);
                if ($contents === false) {
                    throw new \InvalidArgumentException(\sprintf('Cannot read file %s', $file['path']));
                }
            } else {
                $contents = $file['content'];
            }
            $parts[] = ['name' => 'file', 'contents' => $contents, 'filename' => $file['fileName']];
        }
        $request = new Request('POST', '/v1/public/files', null, false, null, $parts, idempotent: false);
        $data = $this->transport->request($request, $options)['data'];
        $uploaded = \is_array($data) ? ($data['files'] ?? null) : null;
        if (!\is_array($uploaded)) {
            throw new UnexpectedResponseException('Unexpected response from RuSender API: POST /v1/public/files returned no files array');
        }

        return FileInfo::list($uploaded);
    }

    /**
     * Gets file metadata: name, size, upload date. Use download() for the contents.
     * Scope: `files.read`.
     *
     * @param RequestOptions $options
     */
    public function get(int $fileId, array $options = []): FileInfo
    {
        return $this->call(Request::get(Path::build('/v1/public/files/{}', $fileId)), $options, FileInfo::class);
    }

    /**
     * Downloads the file contents. Returns the raw response with a streamed body: read it with
     * `(string) $response->getBody()` or copy the stream.
     * Scope: `files.read`.
     *
     * @param RequestOptions $options
     */
    public function download(int $fileId, array $options = []): ResponseInterface
    {
        return $this->raw(Request::get(Path::build('/v1/public/files/{}/download', $fileId)), $options);
    }
}
