# Changelog

## [2.3.0] - 2026-04-21

### Added
- PHP 8.4 and PHP 8.5 compatibility.
- Explicit `php` constraint in `composer.json` (`~8.1.0 || ~8.2.0 || ~8.3.0 || ~8.4.0 || ~8.5.0`).

### Changed
- Added `declare(strict_types=1)` to all PHP classes.
- Added explicit return types on all methods (including `Setup\Patch\Data\UpdateSpeculationRulesConfigPathPatch::apply()`, `getAliases()` and `getDependencies()`).
- Replaced `strpos($s, $needle) === 0` with `str_starts_with()` for clarity.
- Typed nullable parameters explicitly (e.g. `int|string|null $store`) to avoid the PHP 8.4 implicit nullable deprecation.
- Made constructor-promoted properties and helper methods `protected` instead of `private` for easier extension by downstream modules.
- Minor refactor: `unset` of loop reference variable after foreach-by-reference.

### Notes
- No public API change: all previously `public` methods keep the same signatures.
- Extensions that relied on subclassing internals now benefit from `protected` visibility.
