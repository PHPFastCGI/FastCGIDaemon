# Change Log

All notable changes to this project will be documented in this file, in reverse
chronological order by release.

## 0.11.0

### Added

- Added support for Symfony4
- `DaemonOptionsInterface`
- `DaemonFactoryInterface::createDaemonFromStreamSocket`
- Added support for file uploads and multiple file uploads
- Added support for any PSR-7 implementation by using a PSR-17 HTTP Factory.  

### Changed

- Renamed `CallbackWrapper` to `CallbackKernel`.
- All classes are final

### Removed

- Support for PHP5.
- Support for Symfony2.

## 0.10.1

### Fixed

- Large POST request should not fail

## 0.10.0

### Added

- Added a command line flag for gracefully shutting down the daemon after a 5XX
 error code is received.
