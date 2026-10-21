<?php
declare(strict_types=1);

namespace Crustum\Inspector;

use Crustum\Inspector\Detectors\AgentsDetector;
use Crustum\Inspector\Detectors\ApproachDetector;
use Crustum\Inspector\Detectors\BrowserTestFrameworkDetector;
use Crustum\Inspector\Detectors\EditorsDetector;
use Crustum\Inspector\Detectors\FrontendDetector;
use Crustum\Inspector\Detectors\StackDetector;
use Crustum\Inspector\Ecosystems\Ecosystem;
use Crustum\Inspector\Ecosystems\JsEcosystem;
use Crustum\Inspector\Scanners\ComposerLock;
use Crustum\Inspector\Scanners\JsLockfile;
use Crustum\Inspector\Scanners\PackageScanner;
use Crustum\Inspector\Support\ApproachSet;
use Crustum\Inspector\Support\EnumSet;

/**
 * Scanned project surface with ecosystems and detectors.
 *
 * @phpstan-consistent-constructor
 */
class ProjectScan
{
    /**
     * Lazy-loaded approach results.
     *
     * @var \Crustum\Inspector\Support\ApproachSet|null
     */
    protected ?ApproachSet $approaches = null;

    /**
     * Constructor.
     *
     * @param string $basePath Normalized project base path
     * @param \Crustum\Inspector\Ecosystems\Ecosystem $php PHP ecosystem
     * @param \Crustum\Inspector\Ecosystems\JsEcosystem $js JS ecosystem
     * @param \Crustum\Inspector\Support\EnumSet<\Crustum\Inspector\Enums\Stack> $stacks Detected stacks
     * @param \Crustum\Inspector\Support\EnumSet<\Crustum\Inspector\Enums\BrowserTestFramework> $browserTestFrameworks Detected browser frameworks
     * @param \Crustum\Inspector\Support\EnumSet<\Crustum\Inspector\Enums\Frontend> $frontends Detected frontends
     * @param \Crustum\Inspector\Support\EnumSet<\Crustum\Inspector\Enums\Agent> $agents Detected agents
     * @param \Crustum\Inspector\Support\EnumSet<\Crustum\Inspector\Enums\Editor> $editors Detected editors
     * @param string|null $minimumPhpVersion Minimum required PHP major.minor version
     */
    public function __construct(
        protected string $basePath,
        protected Ecosystem $php,
        protected JsEcosystem $js,
        protected EnumSet $stacks,
        protected EnumSet $browserTestFrameworks,
        protected EnumSet $frontends,
        protected EnumSet $agents,
        protected EnumSet $editors,
        protected ?string $minimumPhpVersion = null,
    ) {
    }

    /**
     * PHP package ecosystem.
     *
     * @return \Crustum\Inspector\Ecosystems\Ecosystem
     */
    public function php(): Ecosystem
    {
        return $this->php;
    }

    /**
     * JavaScript package ecosystem.
     *
     * @return \Crustum\Inspector\Ecosystems\JsEcosystem
     */
    public function js(): JsEcosystem
    {
        return $this->js;
    }

    /**
     * Detected application stacks.
     *
     * @return \Crustum\Inspector\Support\EnumSet<\Crustum\Inspector\Enums\Stack>
     */
    public function stacks(): EnumSet
    {
        return $this->stacks;
    }

    /**
     * Detected browser test frameworks.
     *
     * @return \Crustum\Inspector\Support\EnumSet<\Crustum\Inspector\Enums\BrowserTestFramework>
     */
    public function browserTestFrameworks(): EnumSet
    {
        return $this->browserTestFrameworks;
    }

    /**
     * Detected frontends.
     *
     * @return \Crustum\Inspector\Support\EnumSet<\Crustum\Inspector\Enums\Frontend>
     */
    public function frontends(): EnumSet
    {
        return $this->frontends;
    }

    /**
     * Detected AI agents.
     *
     * @return \Crustum\Inspector\Support\EnumSet<\Crustum\Inspector\Enums\Agent>
     */
    public function agents(): EnumSet
    {
        return $this->agents;
    }

    /**
     * Detected editors.
     *
     * @return \Crustum\Inspector\Support\EnumSet<\Crustum\Inspector\Enums\Editor>
     */
    public function editors(): EnumSet
    {
        return $this->editors;
    }

    /**
     * Minimum PHP major.minor version required by the project.
     *
     * Falls back to the running PHP version when the project declares none.
     *
     * @return string
     */
    public function minimumPhpVersion(): string
    {
        return $this->minimumPhpVersion ?? PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
    }

    /**
     * Detected source-code approaches.
     *
     * @return \Crustum\Inspector\Support\ApproachSet
     */
    public function approaches(): ApproachSet
    {
        return $this->approaches ??= new ApproachSet(
            ApproachDetector::detect($this->basePath),
        );
    }

    /**
     * Scan a project directory.
     *
     * @param string|null $basePath Project base path
     */
    public static function scan(?string $basePath = null): static
    {
        $basePath = self::normalizeBasePath($basePath);

        $composer = new ComposerLock($basePath);
        $phpPackages = $composer->scan();

        $jsLockfile = new JsLockfile($basePath);
        $jsPackages = $jsLockfile->scan();

        $php = new Ecosystem($phpPackages);
        $js = new JsEcosystem($jsPackages, $jsLockfile->committedManager());

        return new static(
            $basePath,
            $php,
            $js,
            new EnumSet(StackDetector::detect($php)),
            new EnumSet(BrowserTestFrameworkDetector::detect($php, $js, $basePath)),
            new EnumSet(FrontendDetector::detect($js)),
            new EnumSet(AgentsDetector::detect($basePath)),
            new EnumSet(EditorsDetector::detect($basePath)),
            $composer->minimumPhpVersion(),
        );
    }

    /**
     * Normalize a base path with trailing directory separator.
     *
     * @param string|null $basePath Project base path
     * @return string
     */
    public static function normalizeBasePath(?string $basePath): string
    {
        return PackageScanner::finishPath($basePath ?? (getcwd() ?: '.'));
    }

    /**
     * Serialize scan results for JSON output.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'php' => array_map(fn(Package $package): array => $package->toArray(), $this->php->packages()->all()),
            'js' => array_map(fn(Package $package): array => $package->toArray(), $this->js->packages()->all()),
            'stacks' => $this->stacks->values(),
            'browserTestFrameworks' => $this->browserTestFrameworks->values(),
            'frontends' => $this->frontends->values(),
            'agents' => $this->agents->values(),
            'editors' => $this->editors->values(),
            'minimumPhpVersion' => $this->minimumPhpVersion(),
            'jsPackageManager' => $this->js->packageManager()?->value,
        ];
    }

    /**
     * Encode scan results as JSON.
     *
     * @return string
     */
    public function json(): string
    {
        return self::encode($this->toArray());
    }

    /**
     * Encode a payload as JSON.
     *
     * @param array<string, mixed> $payload Data to encode
     * @return string
     */
    public static function encode(array $payload): string
    {
        return json_encode($payload, JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRESERVE_ZERO_FRACTION) ?: '{}';
    }

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        $properties = get_object_vars($this);
        unset($properties['approaches']);

        return $properties;
    }

    /**
     * @param array<string, mixed> $properties Serialized properties
     * @return void
     */
    public function __unserialize(array $properties): void
    {
        foreach ($properties as $property => $value) {
            $this->{$property} = $value;
        }

        $this->approaches = null;
    }
}
