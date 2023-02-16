BartacusBundle
==============

[![Code Style](https://styleci.io/repos/35467130/shield?style=flat)](https://styleci.io/repos/35467130)


Welcome to the BartacusBundle - the Symfony bundle which integrates the Symfony
full stack framework into your TYPO3 CMS.


## What Bartacus Bundle does

#### Configuration
- Enables `bartacus.make_instance` tagging for services to be added to the DI container
- Enables Symfony error handler in `dev` context instead of TYPO3 error handler
- Enables dependency injection for `UpgradeWizardInterface` implementation
- Auto-mapping of application specific Extbase models and repositories

#### Content Element Rendering
- Adds the `@ContentElement()` annotation and builds the TypoScript config for each element
- Injects `tt_content` Extbase models to Symfony controllers
- Extracts the Symfony response of each controller back to TYPO3 content
- Handles `404 Not Found` exceptions thrown by Symfony controllers

#### Request Handling
- Unifies the `_locale` for TYPO3 and Symfony requests
- Symfony route detection and execution within TYPO3 stack
- Re-orders TYPO3 redirect and route resolver middlewares
