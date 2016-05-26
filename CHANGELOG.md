# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.1.2 - 2016-05-26

### Added

- [#50](https://github.com/zfcampus/zf-content-negotiation/pull/50) adds support
  for parsing `application/hal+json` bodies; `_embedded` properties are now
  merged with the top-level object following parsing.
- [#66](https://github.com/zfcampus/zf-content-negotiation/pull/66) adds suport
  in the `ContentTypeFilterListener` to allow for request bodies to be objects
  that are castable to strings, such as occurs when using zend-psr7bridge to
  convert from PSR-7 to zend-http request instances (the message body is then a
  `StreamInterface` implementation, which may be cast to a string).

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#68](https://github.com/zfcampus/zf-content-negotiation/pull/68) fixes
  parsing of urlencoded data within PUT requests.
- [#52](https://github.com/zfcampus/zf-content-negotiation/pull/52) updates the
  `ContentTypeListener` to raise an error for non-object/non-array JSON payloads.
- [#58](https://github.com/zfcampus/zf-content-negotiation/pull/58) updates the
  `AcceptFilterListener` to validate payloads without an `Accept` header.
- [#63](https://github.com/zfcampus/zf-content-negotiation/pull/63) fixes the
  `ContentTypeListener` behavior when the request body does not contain a MIME
  boundary; the method now catches the exception and returns a 400 response.
