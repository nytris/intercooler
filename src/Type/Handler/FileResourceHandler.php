<?php

/*
 * Nytris Intercooler - PHP heap snapshotting for accelerated bootstrapping.
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/intercooler/
 *
 * Released under the MIT license.
 * https://github.com/nytris/intercooler/raw/main/MIT-LICENSE.txt
 */

declare(strict_types=1);

namespace Nytris\Intercooler\Type\Handler;

use Nytris\Intercooler\Snapshot\Dump\DumpContextInterface;
use Nytris\Intercooler\Snapshot\Dump\ValueDumperInterface;
use Nytris\Intercooler\Snapshot\Statement\StatementBufferInterface;

/**
 * Class FileResourceHandler.
 *
 * Handles snapshotting of PHP stream (file) resources.
 * Generates code to reopen the file at the original path and seek to the original position.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class FileResourceHandler implements TypeHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function canHandle(mixed $value): bool
    {
        return is_resource($value) && get_resource_type($value) === 'stream';
    }

    /**
     * @inheritDoc
     */
    public function dump(
        mixed $value,
        DumpContextInterface $context,
        StatementBufferInterface $buffer,
        ValueDumperInterface $valueDumper
    ): string {
        if ($context->hasResource($value)) {
            return $context->getResourceVarName($value);
        }

        $varName = $context->registerResource($value);
        $meta = stream_get_meta_data($value);
        $uri = $meta['uri'] ?? null;
        $mode = $meta['mode'];
        $position = ftell($value);

        if ($uri === null) {
            // Cannot restore a stream with no URI.
            $buffer->addObjectCreation(
                sprintf('%s = null; // stream resource has no URI and cannot be restored.', $varName)
            );

            return $varName;
        }

        $uriCode = var_export($uri, true);
        $modeCode = var_export($mode, true);

        $creation = sprintf('%s = fopen(%s, %s);', $varName, $uriCode, $modeCode);

        if ($position !== false && $position > 0) {
            $creation .= sprintf(' fseek(%s, %d);', $varName, $position);
        }

        $buffer->addObjectCreation($creation);

        return $varName;
    }
}
