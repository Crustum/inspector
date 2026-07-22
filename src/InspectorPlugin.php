<?php
declare(strict_types=1);

namespace Crustum\Inspector;

use Cake\Cache\Cache;
use Cake\Cache\Engine\FileEngine;
use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Core\ContainerInterface;
use Cake\Core\PluginApplicationInterface;
use Crustum\Inspector\Command\ScanCommand;
use Override;

/**
 * Inspector plugin for detecting packages and approaches in CakePHP projects.
 */
class InspectorPlugin extends BasePlugin
{
    /**
     * Plugin name.
     *
     * @var string|null
     */
    protected ?string $name = 'Inspector';

    /**
     * Enable console commands.
     *
     * @var bool
     */
    protected bool $consoleEnabled = true;

    /**
     * Register plugin cache and other bootstrap logic.
     *
     * @param \Cake\Core\PluginApplicationInterface $app The host application
     * @return void
     */
    #[Override]
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);

        if (Cache::getConfig('inspector') === null) {
            Cache::setConfig('inspector', [
                'className' => FileEngine::class,
                'path' => CACHE . 'inspector' . DS,
                'prefix' => '',
                'serialize' => true,
                'duration' => '+1 hour',
            ]);
        }
    }

    /**
     * Register plugin services in the application container.
     *
     * @param \Cake\Core\ContainerInterface $container Application container
     * @return void
     */
    public function services(ContainerInterface $container): void
    {
        $container->addShared(ProjectManager::class);
        $container->add(ScanCommand::class)
            ->addArgument(ProjectManager::class);
    }

    /**
     * Register plugin console commands.
     *
     * @param \Cake\Console\CommandCollection $commands Command collection
     * @return \Cake\Console\CommandCollection
     */
    #[Override]
    public function console(CommandCollection $commands): CommandCollection
    {
        $commands = parent::console($commands);
        $commands->add('inspector scan', ScanCommand::class);

        return $commands;
    }
}
