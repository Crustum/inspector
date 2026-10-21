<?php
declare(strict_types=1);

namespace Crustum\Inspector\Enums;

/**
 * JavaScript package manager identified from committed lockfiles.
 */
enum JsPackageManager: string
{
    case Npm = 'npm';
    case Pnpm = 'pnpm';
    case Yarn = 'yarn';
    case Bun = 'bun';
    case Nub = 'nub';

    /**
     * Primary lockfile for this manager.
     *
     * @return string
     */
    public function lockFile(): string
    {
        return $this->lockFiles()[0];
    }

    /**
     * Lockfiles that identify this manager.
     *
     * @return list<string>
     */
    public function lockFiles(): array
    {
        return match ($this) {
            self::Npm => ['package-lock.json'],
            self::Pnpm => ['pnpm-lock.yaml'],
            self::Yarn => ['yarn.lock'],
            self::Bun => ['bun.lock', 'bun.lockb'],
            self::Nub => ['nub.lock'],
        };
    }
}
