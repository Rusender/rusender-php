<?php

declare(strict_types=1);

namespace RuSender\Resources;

use RuSender\Http\Request;
use RuSender\Http\Transport;
use RuSender\Support\Path;
use RuSender\Types\DeleteResult;
use RuSender\Types\Page;
use RuSender\Types\Template;
use RuSender\Types\TemplateSummary;

/**
 * Email templates.
 *
 * @phpstan-import-type RequestOptions from Transport
 *
 * @phpstan-type CreateTemplateParams array{name: string, html: string, text?: string, alias?: string}
 * @phpstan-type UpdateTemplateParams array{name?: string, html?: string, text?: string, alias?: string|null}
 */
final class Templates extends AbstractResource
{
    /**
     * Lists templates without HTML, searchable by name. Use get() for the full template.
     * Scope: `templates.read`.
     *
     * @param array{page?: int, limit?: int, name?: string} $params
     * @param RequestOptions                                 $options
     *
     * @return Page<TemplateSummary>
     */
    public function list(array $params = [], array $options = []): Page
    {
        return $this->page(Request::get('/v2/public/templates', $params), $options, TemplateSummary::class);
    }

    /**
     * Creates an HTML template. `alias` (up to 64 characters) lets you send with `templateAlias`.
     * Scope: `templates.write`.
     *
     * @param CreateTemplateParams $params
     * @param RequestOptions       $options
     */
    public function create(array $params, array $options = []): Template
    {
        return $this->call(Request::withBody('POST', '/v1/public/templates', $params), $options, Template::class);
    }

    /**
     * Gets a template with its HTML.
     * Scope: `templates.read`.
     *
     * @param RequestOptions $options
     */
    public function get(int $templateId, array $options = []): Template
    {
        return $this->call(Request::get(Path::build('/v1/public/templates/{}', $templateId)), $options, Template::class);
    }

    /**
     * Updates an HTML template; `alias => null` removes the alias. Builder templates cannot be edited via the API.
     * Scope: `templates.write`.
     *
     * @param UpdateTemplateParams $params
     * @param RequestOptions       $options
     */
    public function update(int $templateId, array $params, array $options = []): Template
    {
        return $this->call(Request::withBody('PATCH', Path::build('/v1/public/templates/{}', $templateId), $params), $options, Template::class);
    }

    /**
     * Deletes a template. Campaigns keep their own copy and are not affected; transactional emails that use this
     * template (`templateId`, `templateAlias`) stop being sent.
     * Scope: `templates.write`.
     *
     * @param RequestOptions $options
     */
    public function delete(int $templateId, array $options = []): DeleteResult
    {
        return $this->call(Request::withoutBody('DELETE', Path::build('/v1/public/templates/{}', $templateId)), $options, DeleteResult::class);
    }
}
