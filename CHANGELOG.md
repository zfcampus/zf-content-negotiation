# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.1.2 - TBD

### Added

- [#50](https://github.com/zfcampus/zf-content-negotiation/pull/50) adds support
  for parsing `application/hal+json` bodies; `_embedded` properties are now
  merged with the top-level object following parsing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#68](https://github.com/zfcampus/zf-content-negotiation/pull/68) fixes
  parsing of urlencoded data within PUT requests.
- [#52](https://github.com/zfcampus/zf-content-negotiation/pull/52) updates the
  `ContentTypeListener` to raise an error for non-object/non-array JSON payloads.
