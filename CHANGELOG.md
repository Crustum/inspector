# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.4] - 2026-10-30

### Fixed

- `CakePluginResolver` prefers the PSR-4 glob (exact on-disk casing) over the composer-name convention guess
- Resolved plugin classes are canonicalized to their declared casing, so namespace-derived Cake names never inherit a miscased guess.

## [1.0.3] - 2026-10-30

### Added

- `Ecosystem::cakePluginDetails()` resolving CakePHP plugin classes, Cake names and install paths for `cakephp-plugin` packages (plus explicit directories), without loading the plugins
- `CakePlugin` value object (`package`, `class`, `name`, `path`, `version`, `direct`, `dev`)

## [1.0.1]

### Added

- `JsPackageManager::Nub` detection via `nub.lock` (pnpm v9 format, scanned with the pnpm parser; lowest lockfile precedence)

## [1.0.0]

### Added

- Core detection API with `ProjectScan` and cached `ProjectManager` (Cake Cache `inspector` store)
- PHP and JS ecosystems with package presence, semver constraints, direct/dev filters, and `PackageCollection`
- Scanners for `composer.lock`, npm/Yarn/pnpm/Bun lockfiles, and `package.json` manifests
- Detectors for agents, editors, frontends, Cake stacks (`Api`, `Bake`, `Queue`, `DefaultView`), and browser test frameworks (`Playwright`, `Cypress`, `Codeception`)
- Approach detection for enum case casing and entity `_accessible` whitelist vs default (`'*'`)
- `JsEcosystem::usesPackageManager()` for committed lockfile manager checks
- `inspector scan` console command with `--approaches` and `--cached` flags
- `ProjectScan::encode()` with `JSON_PRESERVE_ZERO_FRACTION` so whole confidence values stay JSON floats
