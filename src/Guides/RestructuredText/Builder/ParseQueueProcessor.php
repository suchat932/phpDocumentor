<?php

declare(strict_types=1);

namespace phpDocumentor\Guides\RestructuredText\Builder;

use League\Flysystem\FilesystemInterface;
use League\Tactician\CommandBus;
use phpDocumentor\Guides\Files;
use phpDocumentor\Guides\RestructuredText\Command\ParseFileCommand;
use phpDocumentor\Guides\Configuration;
use phpDocumentor\Guides\RestructuredText\Kernel;

class ParseQueueProcessor
{
    /** @var CommandBus */
    private $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function process(Kernel $kernel, Files $parseQueue, FilesystemInterface $origin, string $currentDirectory) : void
    {
        $this->guardThatAnIndexFileExists($origin, $currentDirectory, $kernel->getConfiguration());

        foreach ($parseQueue as $file) {
            $this->commandBus->handle(new ParseFileCommand($kernel, $origin, $currentDirectory, $file));
        }
    }

    private function guardThatAnIndexFileExists(FilesystemInterface $filesystem, string $directory, Configuration $configuration): void
    {
        $indexName = $configuration->getNameOfIndexFile();
        $extension = $configuration->getSourceFileExtension();
        $indexFilename = sprintf('%s.%s', $indexName, $extension);
        if (!$filesystem->has($directory . '/' . $indexFilename)) {
            throw new \InvalidArgumentException(sprintf('Could not find index file "%s" in "%s"', $indexFilename, $directory));
        }
    }
}
