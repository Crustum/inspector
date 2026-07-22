<?php
declare(strict_types=1);

namespace Crustum\Inspector\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Crustum\Inspector\ApproachResult;
use Crustum\Inspector\ProjectManager;
use Crustum\Inspector\ProjectScan;
use Override;

/**
 * Detect project packages, stacks, and approaches and output JSON.
 */
class ScanCommand extends Command
{
    /**
     * @param \Crustum\Inspector\ProjectManager $projectManager Cached project scanner
     */
    public function __construct(protected ProjectManager $projectManager)
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public static function defaultName(): string
    {
        return 'inspector scan';
    }

    /**
     * Get the command description.
     *
     * @return string
     */
    #[Override]
    public static function getDescription(): string
    {
        return 'Detect packages, stacks, frameworks, agents, and approaches in use and output as JSON';
    }

    /**
     * Execute the command.
     *
     * @param \Cake\Console\Arguments $args Command arguments
     * @param \Cake\Console\ConsoleIo $io Console IO
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $directory = $args->getArgument('directory');
        $basePath = is_string($directory) ? $directory : null;
        $resolved = ProjectScan::normalizeBasePath($basePath);

        if (!is_dir($resolved) || !is_readable($resolved)) {
            $io->error("Directory '{$resolved}' is not a readable directory.");

            return static::CODE_ERROR;
        }

        $project = $args->getOption('cached')
            ? $this->projectManager->scan($basePath)
            : $this->projectManager->fresh($basePath);
        $payload = $project->toArray();

        if ($args->getOption('approaches')) {
            $payload['approaches'] = $project->approaches()->all()
                ->map(fn(ApproachResult $result): array => $result->toArray())
                ->toArray();
        }

        $io->out(ProjectScan::encode($payload));

        return static::CODE_SUCCESS;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);
        $parser->setDescription(static::getDescription());
        $parser->addArgument('directory', [
            'help' => 'Project directory to scan (defaults to the current working directory)',
            'required' => false,
        ]);
        $parser->addOption('approaches', [
            'help' => 'Detect source-code approaches by scanning PHP source files',
            'boolean' => true,
            'default' => false,
        ]);
        $parser->addOption('cached', [
            'help' => 'Read and write scan results through the inspector cache store',
            'boolean' => true,
            'default' => false,
        ]);

        return $parser;
    }
}
