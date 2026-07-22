# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
