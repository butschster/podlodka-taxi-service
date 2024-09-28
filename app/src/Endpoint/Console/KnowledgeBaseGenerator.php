<?php

declare(strict_types=1);

namespace App\Endpoint\Console;

use Spiral\Boot\DirectoriesInterface;
use Spiral\Console\Attribute\AsCommand;
use Spiral\Console\Command;
use Spiral\Files\FilesInterface;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'kb:generate',
    description: 'Generate a code base example for agents and tools.',
)]
final class KnowledgeBaseGenerator extends Command
{
    private FilesInterface $files;
    private string $outputDir;
    private DirectoriesInterface $dirs;
    private string $basePath;

    public function __invoke(
        DirectoriesInterface $dirs,
        FilesInterface $files,
    ): int {
        $this->dirs = $dirs;
        $this->basePath = $dirs->get('root');
        $this->outputDir = $dirs->get('runtime') . '/knowledge-base';
        $this->files = $files;
        $files->ensureDirectory(directory: $this->outputDir);

        // Temporal Workflow
        $this->writeContent(
            description: <<<'TEXT'
Temporal Workflow
TEXT,
            sourcePath: [
                $dirs->get('app') . 'src/Endpoint/Temporal',
            ],
            outputPath: $this->outputDir . '/temporal-layer.txt',
        );

        // Domain Layer
        $this->writeContent(
            description: <<<'TEXT'
Domain Layer
TEXT,
            sourcePath: [
                $dirs->get('app') . 'Taxi',
            ],
            outputPath: $this->outputDir . '/domain-layer.txt',
        );

        // Application Layer
        $this->writeContent(
            description: <<<'TEXT'
Application Layer
TEXT,
            sourcePath: [
                $dirs->get('app') . 'src',
            ],
            outputPath: $this->outputDir . '/application-layer.txt',
        );


        return self::SUCCESS;
    }

    private function writeContent(
        string $description,
        string|array $sourcePath,
        string $outputPath,
        string $pattern = '*.php',
    ): void {
        $found = Finder::create()->name($pattern)->in($sourcePath);

        $description .= PHP_EOL;

        foreach ($found as $file) {
            $description .= '//' . \trim(\str_replace($this->basePath, '', $file->getPath())) . PHP_EOL;
            $description .= \str_replace(['<?php', 'declare(strict_types=1);'], '', $file->getContents()) . PHP_EOL;
        }

        $description = \preg_replace('/^\s*[\r\n]+/m', '', $description);

        $this->info('Writing ' . $outputPath);
        $this->files->write($outputPath, $description);
    }
}
