# CakePHP Inspector Plugin

The **Inspector** plugin detects packages and development approaches in CakePHP projects by scanning Composer and JavaScript lockfiles, filesystem markers, and optionally PHP source conventions.

Inspector reports Composer and npm-family dependencies, application stacks and frontends, browser test frameworks, AI agents and editors, the JS package manager indicated by the committed lockfile, and stylistic approaches such as enum case casing and entity accessibility patterns.

The plugin integrates with CakePHP's plugin system and Cache layer, exposes a `ProjectManager` / `ProjectScan` API for programmatic use, and provides a `inspector scan` console command that emits JSON.

## Requirements

* PHP 8.2+

See [Versions.md](docs/Versions.md) for the supported CakePHP versions.

## Documentation

For documentation, as well as usage examples, see the [docs](docs/index.md) directory of this repository.

## License

Licensed under the [MIT](http://www.opensource.org/licenses/mit-license.php) License. Redistributions of the source code included in this repository must retain the copyright notice found in each file.
