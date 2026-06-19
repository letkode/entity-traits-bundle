# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

---

## [1.0.0] - 2026-06-19

### Added
- Initial release as `letkode/entity-traits-bundle`
- Symfony bundle integration via `LetkodeEntityTraitsBundle` extending `AbstractBundle` + `PrependExtensionInterface`
- Auto-discovery support via `extra.symfony.bundles` in Composer
- Registers `TRANSLATE_FIELD_VALUE` Doctrine DQL function automatically via `prepend()`
- **Entity traits**: `UuidTrait` (UUIDv7 with DB default), `HasTranslationsTrait` (jsonb translations map), `ParameterTrait` (jsonb parameters with recursive merge), `ObjectTrackNullableTrait`, `ObjectTrackRequiredTrait`
- **Repository traits**: `BaseRepositoryTrait` (save, remove, paginate, findByUuid, findOrFailByUuid), `TranslatableRepositoryTrait` (addTranslatedOrderBy, addTranslatedSearch)
- **DTOs**: `PaginatedResult`, `TableQueryRequest` (with `fromArray()` factory)
- **Value Objects**: `Email`, `Phone` (E.164), `Slug`, `Username` — all validated and normalized at construction, throw `ValueObjectException`
- **Doctrine**: `UuidGeneratorSubscriber` (pre-persist PHP UUID generation for Gedmo Loggable compatibility), `DQL/TranslateFieldValue` (`jsonb_extract_path_text` wrapper)
- **Attribute**: `#[Translatable]` for marking translatable entity fields

### Requirements
- PHP `^8.4`
- Symfony `^7.0 || ^8.0`
- `doctrine/orm` `^3.0`
- `doctrine/bundle` `^2.0`
- `gedmo/doctrine-extensions` `^3.0`
- `letkode/common-bundle` `^1.0`

[Unreleased]: https://github.com/letkode/entity-traits-bundle/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/letkode/entity-traits-bundle/releases/tag/v1.0.0
