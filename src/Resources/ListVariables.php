<?php

declare(strict_types=1);

namespace RuSender\Resources;

use RuSender\Http\Request;
use RuSender\Http\Transport;
use RuSender\Support\Path;
use RuSender\Types\DeleteResult;
use RuSender\Types\Variable;

/**
 * Variables of a list: `$rusender->lists->variables`.
 *
 * @phpstan-import-type RequestOptions from Transport
 *
 * @phpstan-type CreateVariableParams array{key: string, name: string, type?: 'string'|'phone', defaultValue?: string}
 * @phpstan-type UpdateVariableParams array{name?: string, defaultValue?: string|null}
 */
final class ListVariables extends AbstractResource
{
    /**
     * Lists the variables of a list.
     * Scope: `contacts.read`.
     *
     * @param RequestOptions $options
     *
     * @return list<Variable>
     */
    public function list(int $listId, array $options = []): array
    {
        return $this->items(Request::get(Path::build('/v1/public/lists/{}/variables', $listId)), $options, Variable::class);
    }

    /**
     * Creates a list variable. `key` is used in `variables` of contacts and in templates.
     * Scope: `contacts.write`.
     *
     * @param CreateVariableParams $params
     * @param RequestOptions       $options
     */
    public function create(int $listId, array $params, array $options = []): Variable
    {
        return $this->call(Request::withBody('POST', Path::build('/v1/public/lists/{}/variables', $listId), $params), $options, Variable::class);
    }

    /**
     * Updates the name or default value of a list variable; `defaultValue => null` clears the default.
     * Scope: `contacts.write`.
     *
     * @param UpdateVariableParams $params
     * @param RequestOptions       $options
     */
    public function update(int $listId, int $variableId, array $params, array $options = []): Variable
    {
        $request = Request::withBody('PATCH', Path::build('/v1/public/lists/{}/variables/{}', $listId, $variableId), $params);

        return $this->call($request, $options, Variable::class);
    }

    /**
     * Deletes a list variable together with its values in contacts.
     * Scope: `contacts.write`.
     *
     * @param RequestOptions $options
     */
    public function delete(int $listId, int $variableId, array $options = []): DeleteResult
    {
        $request = Request::withoutBody('DELETE', Path::build('/v1/public/lists/{}/variables/{}', $listId, $variableId));

        return $this->call($request, $options, DeleteResult::class);
    }
}
