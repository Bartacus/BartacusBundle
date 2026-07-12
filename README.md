BartacusBundle
==============

Welcome to the BartacusBundle - the Symfony bundle + TYPO3 extension which integrates the Symfony framework fully into your TYPO3 v14.3.* CMS.

## What Bartacus Bundle does

BartacusBundle transforms TYPO3 into a Symfony-first environment, allowing developers to build TYPO3 extensions and websites using modern Symfony patterns while retaining the powerful content management capabilities of TYPO3.

### Full Symfony Stack in TYPO3
It initializes the project's Symfony kernel at the very beginning of the PHP process. This properly injects the full Symfony stack into TYPO3 and allows the project source code to be written as if it were a standard Symfony application.

### Event-driven Configuration
Bartacus uses a `ConfigLoader` to hook into TYPO3's configuration lifecycle (AdditionalConfiguration, RequestMiddlewares, Extbase Persistence) via Symfony Events. This allows developers to extend or modify TYPO3's configuration using standard Symfony event listeners.

### Symfony Controllers as Content Elements
You can use Symfony controllers as TYPO3 content elements by simply adding the `#[ContentElement()]` attribute to your controller methods. 
- Automatically generates TypoScript configuration.
- Converts TYPO3 content object data into Symfony request/models.
- Handles `NotFoundHttpException` by triggering TYPO3's native 404 error handling.

### Integrated Symfony Routing
The bundle allows you to define standard Symfony routes that coexist with TYPO3 page requests. When a Symfony route is hit:
1. It intercepts the request early in the TYPO3 middleware stack.
2. It fakes a TYPO3 request context to ensure TYPO3 configurations (like TypoScript) are loaded.
3. It handles the request via the Symfony Kernel and returns the response to the browser.

### Service Interoperability
BartacusBundle provides deep integration between the Symfony Container and TYPO3's `GeneralUtility`:
- **Symfony Services in TYPO3**: Services tagged with `bartacus.make_instance` are automatically available via `GeneralUtility::makeInstance()`. This is achieved by injecting a `ServiceLocator` directly into TYPO3's `singletonInstances`.
- **TYPO3 Services in Symfony**: The `ServiceBridge` provides a clean API to access TYPO3's global state (Backend/Frontend User, LanguageService, etc.) and core components are registered as Symfony services for easy injection.

### Unified Request Localization
Normalizes the locale between TYPO3 and Symfony to ensure consistent language handling. The base locale is extracted with the following priority:
1. TYPO3 site language object from request attributes.
2. Symfony router `_locale` parameter.
3. TYPO3 site default language object.

It ensures the locale format is consistent (e.g., `de_AT` for Symfony requests, while the Symfony router uses the URL-friendly `de-at`).

### Static Routes and Optimized Middleware
The bundle overrides the loading order of TYPO3 core middlewares and introduces a custom `StaticRouteMiddleware`. This ensures that static routes (like `/robots.txt`) are handled early via the `StaticRouteEvent`, avoiding unnecessary database-heavy operations. It even includes a default subscriber to generate SEO-friendly `robots.txt` files automatically.

### Enhanced Error Handling
Overrides both Symfony and TYPO3 error/exception handlers to provide a unified, detail-rich Symfony error page, greatly improving the debugging experience within the TYPO3 context.

### Advanced Twig and Template Integration
Bartacus provides a powerful bridging layer between Twig and TYPO3:
- **`TWIGTEMPLATE` Content Object**: A new TYPO3 Content Object that allows you to render Twig templates directly from TypoScript. It supports variable passing (via nested cObjects), `stdWrap`, and even rendering into TYPO3's header/footer data blocks.
- **`bartacus_cobject` Twig Function**: Allows you to execute and render any TYPO3 Content Object (like `RECORDS`, `CONTENT`, or `IMAGE`) directly from your Twig templates, with full support for data and table context.
- **`controller(...)` Twig Function**: Enables you to execute and render Symfony controllers directly from your Twig templates, seamlessly mixing Symfony logic with your template rendering.

---

## How it Works

### 1. Early Kernel Bootstrap
The bundle requires patching TYPO3's entry points (`public/index.php` and `vendor/bin/typo3`) to initialize the `SymfonyBootstrap` class. This class boots the Symfony Kernel before TYPO3 starts, making the Symfony Container available throughout the entire TYPO3 lifecycle.

### 2. Service Container Hooking
During the bundle's boot process, it manipulates `GeneralUtility`'s internal state. It injects a `ServiceLocator` into `GeneralUtility::singletonInstances` and updates the `finalClassNameCache`. This "infects" TYPO3's service resolution, allowing Symfony to fulfill requests for instances that it manages.

### 3. Middleware Interception
The bundle registers several middlewares in the TYPO3 Frontend stack:
- **Static Route Middleware**: Intercepts requests for static paths and dispatches a `StaticRouteEvent`.
- **Route Resolver**: Checks for Symfony route matches before TYPO3 tries to resolve a page.
- **Content Element Renderer**: Handles the execution of controllers for content elements during the page rendering process.

### 4. Data Bridging (ServiceBridge)
The `ServiceBridge` acts as a central hub for TYPO3 integration. It allows you to safely access global state and provides helper methods for common TYPO3 tasks (like getting the current backend user or rendering a content object) without relying on global variables.

### 5. Event-driven Configuration (ConfigLoader)
The `ConfigLoader` acts as a bridge during the TYPO3 configuration phase. It dispatches Symfony events that allow the bundle (and other Symfony bundles) to inject configuration like custom middlewares or Extbase persistence classes directly into TYPO3.

### 6. Template Bridging
The `template` namespace provides a bi-directional bridge for rendering. It allows TYPO3 to use Twig as a rendering engine via the `TWIGTEMPLATE` cObject, and allows Twig templates to pull in TYPO3 content via the `bartacus_cobject` function.

---

## Patches

The bundle relies on several patches to achieve this deep integration:

- `allow_extbase_access_in_cli_mode.patch`: Enables TYPO3 Extbase usage in CLI by faking a request object.
- `enable_symfony_serviceBridge.patch`: Injects the custom service bridge into `GeneralUtility`.
- `proper_symfony_kernel_bootstrap_cli.patch`: Patches `vendor/bin/typo3` for early kernel boot.
- `proper_symfony_kernel_bootstrap_web.patch`: Patches `public/index.php` for early kernel boot.
