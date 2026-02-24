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

namespace Nytris\Intercooler\Snapshot\Write;

use Nytris\Intercooler\Snapshot\Statement\StatementBufferInterface;

/**
 * Class SnapshotWriter.
 *
 * Writes a populated StatementBuffer to a PHP snapshot file on disk.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class SnapshotWriter implements SnapshotWriterInterface
{
    /**
     * @inheritDoc
     */
    public function write(string $snapshotPath, StatementBufferInterface $buffer): void
    {
        $lines = [];
        $lines[] = '<?php';
        $lines[] = '';
        $lines[] = '/*';
        $lines[] = ' * Nytris Intercooler heap snapshot.';
        $lines[] = ' * Generated at: ' . date('Y-m-d H:i:s') . '.';
        $lines[] = ' * Do not edit manually.';
        $lines[] = ' */';
        $lines[] = '';
        $lines[] = 'declare(strict_types=1);';
        $lines[] = '';

        $creations = $buffer->getObjectCreationStatements();

        if (!empty($creations)) {
            $lines[] = '// Object/resource creation.';

            foreach ($creations as $statement) {
                $lines[] = $statement;
            }

            $lines[] = '';
        }

        $propSetups = $buffer->getObjectPropertyStatements();

        if (!empty($propSetups)) {
            $lines[] = '// Object property setup.';

            foreach ($propSetups as $statement) {
                $lines[] = $statement;
            }

            $lines[] = '';
        }

        $staticSetups = $buffer->getStaticPropertyStatements();

        if (!empty($staticSetups)) {
            $lines[] = '// Static property setup.';

            foreach ($staticSetups as $statement) {
                $lines[] = $statement;
            }

            $lines[] = '';
        }

        $globals = $buffer->getGlobalStatements();

        if (!empty($globals)) {
            $lines[] = '// Global variable setup.';

            foreach ($globals as $statement) {
                $lines[] = $statement;
            }

            $lines[] = '';
        }

        file_put_contents($snapshotPath, implode("\n", $lines));
    }
}
