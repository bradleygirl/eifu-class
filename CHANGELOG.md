# Changelog

All notable changes to this plugin are documented here.
Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
Versioning: [Semantic Versioning](https://semver.org/).

## Pre-1.0.0 history

The plugin operated under a `0.1.0` plugin-header label for several
years in production but had no formal release tracking, no
`CHANGELOG.md`, and no tagged releases. The 1.0.0 entry below is the
first formally released version. The `0.1.0` value did not reflect
maturity — the IFU subsystem has been live and serving regulatory
content (~24 production `ifu` posts) for years. 1.0.0 reconciles the
label with reality.

## [Unreleased]

## [1.0.0] - 2026-04-29

### Changed
- PHP 8+ named-argument `define()` syntax converted to positional
  syntax throughout `eifu-class.php` — removes the named-argument
  fatal that fires on PHP < 8.0 (triage 4-05).
- `Requires PHP: 8.0` header added to declare the actual minimum.

### Fixed
- Typo in four bootstrap constant names: `EIFUC_GLOBAl_*` →
  `EIFUC_GLOBAL_*` (uppercase L in "GLOBAL"). The four constants
  (`VERSION`, `NAME`, `ABSPATH`, `BASE_NAME`) are declared in the
  bootstrap but never referenced anywhere else in the codebase, so
  the rename does not affect runtime behavior; the fix prevents
  future typo-propagation if any of the four ever get wired up.

### Removed
- `inc/eifuc-classes.php` — 521 lines of dead scaffolding from an
  earlier internal iteration. The class `IFU_Post_Type` was never
  wired into the live subsystem, which uses `IFU_Post_Register`
  from `inc/posts/register.php` (triage 4-14).
- Nested duplicate plugin tree at `eifu-class/eifu-class/` — a
  full second copy of the bootstrap, `inc/`, `tmpl/`, `LICENSE`,
  and `readme.md`. WordPress only loads the top-level copy; the
  nested tree was dead weight that misled tree navigation.
  Includes deletion of the partial in-progress
  `__eifuc-classes.php` rename inside the nested copy.
- Malformed commented-out `define()` line in `eifu-class.php`
  (syntactically invalid even when active; had been commented
  out historically).

### Security
- `inc/admin/settings.php`: admin settings page now wraps the
  `$message` echo in `wp_kses_post()` for defense-in-depth output
  escaping. Source string is admin-controlled and a translatable
  literal, so risk was already low — change brings the surface in
  line with standard WP idiom (triage 4-18).

### Documentation
- `README.md` added (replacing the previous lowercase `readme.md`)
  with regulatory public-by-design policy note, serialized-LIKE
  reverse-lookup constraint, and uninstall-behavior note (triage
  4-13, 4-15, plus regulatory settled fact #6).
- `CLAUDE.md` (Layer 2) added — repo-specific scope and release
  procedure for Claude Code execution sessions.
- `.gitignore` added with `.claude/` to exclude Claude Code
  workspace metadata (triage 1-10).
- `CHANGELOG.md` (this file) added per deployment-playbook §4.
