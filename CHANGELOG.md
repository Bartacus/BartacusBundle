# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## [2.0.2] - 2019-04-18
### Changed
- Handle TYPO3 page rendering with all the usual Symfony events around as master request
- Dispatch all the usual Symfony events around each content element as sub request
- Handle Symfony routing before the page resolver
- Correctly resolve locale either from `_locale` attribute or TYPO3 site language

## [2.0.1] - 2019-04-12
### Added
- Add relevant `TYPO3\CMS\Core\Context\Context` as service

### Fixed
- Use a patch to make TYPO3 own PSR-7 implementation really compliant

## [2.0.0] - 2019-04-05
### Added
- Register `TYPO3\CMS\Install\Updates\UpgradeWizardInterface` for auto configuration with `bartacus.make_instance` tags

### Changed
- Support for TYPO3 9.5 only
- Minimal required Symfony version is 4.2
- Symfony translator locale is now retrieved from site settings instead of TypoScript config

### Removed
- The `$GLOBALS['kernel']` variable is removed, use `SymfonyBootstrap::getKernel()` instead
- The `SymfonyBootstrap::initAppPackage()` is removed
- The `SYMFONY_ENV` variable is removed, use `APP_ENV` instead
- The `SYMFONY_DEBUG` variable is removed, use `APP_DEBUG` instead
- The following public typo3 services are removed with the old id format, inject the FCQN instead
  - `typo3`, inject `Bartacus\Bundle\BartacusBundle\Typo3\ServiceBridge` instead
  - `typo3.backend_user`, inject `TYPO3\CMS\Core\Authentication\BackendUserAuthentication` instead
  - `typo3.frontend_user`, inject `TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication` instead
  - `typo3.cache.cache_manager`, inject `TYPO3\CMS\Core\Cache\CacheManager` instead
  - `typo3.cache_hash_calculator`, inject `TYPO3\CMS\Frontend\Page\CacheHashCalculator` instead
  - `typo3.content_object_renderer`, inject `TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer` instead
  - `typo3.file_repository`, inject `TYPO3\CMS\Core\Resource\FileRepository` instead
  - `typo3.frontend_controller`, inject `TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` instead
  - `typo3.page_repository`, inject `typo3.page_repository` instead
  - `typo3.registry`, inject `TYPO3\CMS\Core\Registry` instead
- The `typo3.db` and `TYPO3\CMS\Core\Database\DatabaseConnection` are removed. Use `TYPO3\CMS\Core\Database\ConnectionPool` instead

## [1.2.2] - 2019-02-11
### Fixed
- Add missing service bridge function to get objects from the Extbase object manager
- Compatibility with TYPO3 8.7.24 and upwards

## [1.2.1] - 2019-01-25
### Fixed
- Don't use the deprecated typo3 services internally 

## [1.2.0] - 2019-01-21
### Changed
- Adding support for Symfony 4, dropping support for Symfony 3
- Compatibility with helhum/typo3-console 5.6.0

### Deprecated
- The `$GLOBALS['kernel']` variable is deprecated, use `SymfonyBootstrap::getKernel()` instead
- The `SymfonyBootstrap::initAppPackage()` is deprecated
- The `SYMFONY_ENV` variable is deprecated, use `APP_ENV` instead
- The `SYMFONY_DEBUG` variable is deprecated, use `APP_DEBUG` instead
- The following public typo3 services are deprecated with the old id format, inject the FCQN instead
  - `typo3`, inject `Bartacus\Bundle\BartacusBundle\Typo3\ServiceBridge` instead
  - `typo3.backend_user`, inject `TYPO3\CMS\Core\Authentication\BackendUserAuthentication` instead
  - `typo3.frontend_user`, inject `TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication` instead
  - `typo3.cache.cache_manager`, inject `TYPO3\CMS\Core\Cache\CacheManager` instead
  - `typo3.cache_hash_calculator`, inject `TYPO3\CMS\Frontend\Page\CacheHashCalculator` instead
  - `typo3.content_object_renderer`, inject `TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer` instead
  - `typo3.file_repository`, inject `TYPO3\CMS\Core\Resource\FileRepository` instead
  - `typo3.frontend_controller`, inject `TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` instead
  - `typo3.page_repository`, inject `typo3.page_repository` instead
  - `typo3.registry`, inject `TYPO3\CMS\Core\Registry` instead
- The `typo3.db` and `TYPO3\CMS\Core\Database\DatabaseConnection` is deprecated and removed with TYPO3v9. Use `TYPO3\CMS\Core\Database\ConnectionPool` instead

## [1.1.13] - 2019-02-11
### Fixed
- Add missing service bridge function to get objects from the Extbase object manager
- Compatibility with TYPO3 8.7.24 and upwards

## [1.1.12] - 2018-12-20
### Fixed
- Add missing filter controller event to content element renderer (#84)

## [1.1.11] - 2018-12-20
### Fixed
- Compatibility with TYPO3 8.7.22

## [1.1.10] - 2018-11-14
### Fixed
- Compatibility with TYPO3 8.7.20

## [1.1.9] - 2018-08-06
### Fixed
- Add default value for new param on request termination call

## [1.1.8] - 2018-08-03
### Fixed
- Add checks to not use the symfony response in the request handler

## [1.1.7] - 2018-08-02
### Fixed
- Don't create PSR-7 response on symfony routes, send directly

## [1.1.6] - 2018-07-23
### Fixed
- Compatibility with TYPO3 8.7.17

## [1.1.5] - 2018-06-13
### Fixed
- Compatibility with TYPO3 8.7.16

## [1.1.4] - 2018-05-24
### Fixed
- Compatibility with TYPO3 8.7.15

## [1.1.3] - 2018-03-08
### Fixed
- Compatibility with TYPO3 8.7.10

## [1.1.2] - 2018-01-24
### Fixed
- Fix invalid value for Content-Length header

## [1.1.1] - 2018-01-24
### Fixed
- Compatibility with TYPO3 8.7.9

## [1.1.0] - 2017-10-18
### Added
- Register TYPO3 ConnectionPool as Symfony service

## [1.0.1] - 2017-09-08
### Fixed
- Compatibility with TYPO3 8.7.6
- Add patch for new typo3/cms-cli entry point

## [1.0.0] - 2017-09-1
### Changed
- Release for TYPO3 8.7 LTS

[Unreleased]: https://github.com/Bartacus/BartacusBundle/compare/2.0.2...HEAD
[2.0.2]: https://github.com/Bartacus/BartacusBundle/compare/2.0.1...2.0.2
[2.0.1]: https://github.com/Bartacus/BartacusBundle/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/Bartacus/BartacusBundle/compare/1.2.2...2.0.0
[1.2.2]: https://github.com/Bartacus/BartacusBundle/compare/1.2.1...1.2.2
[1.2.1]: https://github.com/Bartacus/BartacusBundle/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/Bartacus/BartacusBundle/compare/1.1.12...1.2.0
[1.1.13]: https://github.com/Bartacus/BartacusBundle/compare/1.1.12...1.1.13
[1.1.12]: https://github.com/Bartacus/BartacusBundle/compare/1.1.11...1.1.12
[1.1.11]: https://github.com/Bartacus/BartacusBundle/compare/1.1.10...1.1.11
[1.1.10]: https://github.com/Bartacus/BartacusBundle/compare/1.1.9...1.1.10
[1.1.9]: https://github.com/Bartacus/BartacusBundle/compare/1.1.8...1.1.9
[1.1.8]: https://github.com/Bartacus/BartacusBundle/compare/1.1.7...1.1.8
[1.1.7]: https://github.com/Bartacus/BartacusBundle/compare/1.1.6...1.1.7
[1.1.6]: https://github.com/Bartacus/BartacusBundle/compare/1.1.5...1.1.6
[1.1.5]: https://github.com/Bartacus/BartacusBundle/compare/1.1.4...1.1.5
[1.1.4]: https://github.com/Bartacus/BartacusBundle/compare/1.1.3...1.1.4
[1.1.3]: https://github.com/Bartacus/BartacusBundle/compare/1.1.2...1.1.3
[1.1.2]: https://github.com/Bartacus/BartacusBundle/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/Bartacus/BartacusBundle/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/Bartacus/BartacusBundle/compare/1.0.1...1.1.0
[1.0.1]: https://github.com/Bartacus/BartacusBundle/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/Bartacus/BartacusBundle/compare/d84fd9f...1.0.0
