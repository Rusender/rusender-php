<?php

declare(strict_types=1);

namespace RuSender\Support;

/** @internal */
final class Path
{
    /** Fills `{}` in a path with encoded parameters. */
    public static function build(string $template, int|string ...$params): string
    {
        $parts = explode('{}', $template);
        if (\count($parts) !== \count($params) + 1) {
            throw new \LogicException(\sprintf('Path %s takes %d parameters, %d given', $template, \count($parts) - 1, \count($params)));
        }
        $path = $parts[0];
        foreach (array_values($params) as $i => $param) {
            $path .= rawurlencode((string) $param) . $parts[$i + 1];
        }

        return $path;
    }
}
