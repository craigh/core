# CHANGELOG - ZIKULA 3.0.x

## 3.0.0 (unreleased)

- BC Breaks:
  - Minimum PHP version is now 7.2.5 instead of 5.5.9 (#3935). PHP 7.2.5+ is also required by Symfony 5.
  - The directory structure is dramatically different (reflecting changes from Symfony).
    - The `public/` directory is now the *web root*. Set your server/htaccess/etc accordingly.
    - `public/index.php` is the entry point to the site.
    - See <https://symfony.com/doc/current/setup/web_server_configuration.html> for more information.
  - Service definitions have been updated to use Symfony autowiring and autoconfiguring functionality (#3940, #3872). This includes autowiring entity repositories by inheriting from `Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository`.
  - Namespace changes
    - `Zikula\Bridge\HttpFoundation\` moved to `Zikula\Bundle\CoreBundle\HttpFoundation\Session\`.
    - `Zikula\Bundle\CoreBundle\Bundle\AbstractCoreModule` moved into `Zikula\ExtensionsModule\`.
    - `Zikula\Bundle\CoreBundle\Bundle\AbstractCoreTheme` moved into `Zikula\ExtensionsModule\`.
    - `Zikula\Bundle\CoreBundle\Bundle\Bootstrap` moved and renamed to `Bundle\CoreBundle\Helper\PersistedBundleHelper`.
    - `Zikula\Bundle\CoreBundle\Bundle\Helper\BootstrapHelper` moved and renamed to `Bundle\CoreBundle\Helper\BundlesSchemaHelper`.
    - `Zikula\Bundle\CoreBundle\Bundle\MetaData` moved into `Zikula\Bundle\CoreBundle\Composer\`.
    - `Zikula\Bundle\CoreBundle\Bundle\Scanner` moved into `Zikula\Bundle\CoreBundle\Composer\`.
    - `Zikula\Common\Collection\` moved into `Zikula\Bundle\CoreBundle\Collection\`.
    - `Zikula\Common\Content\` moved into `Zikula\ExtensionsModule\ModuleInterface\Content\`.
    - `Zikula\Common\MultiHook\` moved into `Zikula\ExtensionsModule\ModuleInterface\MultiHook\`.
    - `Zikula\Common\Translator\` moved into `Zikula\Bundle\CoreBundle\Translation\`.
    - `Zikula\Common\ColumnExistsTrait` moved into `Zikula\Bundle\CoreBundle\Doctrine\`.
    - `Zikula\Core\AbstractBundle` moved and renamed to `Zikula\ExtensionsModule\AbstractExtension`
    - `Zikula\Core\AbstractExtensionInstaller` moved into `Zikula\ExtensionsModule\Installer\`.
    - `Zikula\Core\AbstractModule` moved into `Zikula\ExtensionsModule\`.
    - `Zikula\Core\Controller\` moved into `Zikula\Bundle\CoreBundle\Controller\`.
    - `Zikula\Core\CoreEvents` was split into `Zikula\Bundle\CoreBundle\CoreEvents` and `Zikula\ExtensionsModule\ExtensionEvents`.
    - `Zikula\Core\Doctrine\` moved into `Zikula\Bundle\CoreBundle\Doctrine\`.
    - `Zikula\Core\Event\GenericEvent` moved into `Zikula\Bundle\CoreBundle\Event\`.
    - `Zikula\Core\Event\ModuleStateEvent` moved into `Zikula\ExtensionsModule\Event\`.
    - `Zikula\Core\ExtensionInstallerInterface` moved into `Zikula\ExtensionsModule\Installer\`.
    - `Zikula\Core\InstallerInterface` moved into `Zikula\ExtensionsModule\Installer\`.
    - `Zikula\Core\Response\` moved into `Zikula\Bundle\CoreBundle\Response\`.
    - `Zikula\Core\RouteUrl` moved into `Zikula\Bundle\CoreBundle\`.
    - `Zikula\Core\UrlInterface` moved into `Zikula\Bundle\CoreBundle\`.
    - `Zikula\ThemeModule\AbstractTheme` moved into `Zikula\ExtensionsModule\`.
  - Interface extensions and amendments
    - Removed second argument (`$first = true`) from `ZikulaHttpKernelInterface` methods `getModule`, `getTheme` and `isBundle` (#3377).
    - `ZikulaHttpKernelInterface` has dropped `getConnectionConfig()` method. Use environment variable `DATABASE_URL` instead.
    - In general, interfaces and apis implement argument type-hinting in all methods. This can break an implementation of said interfaces, etc.
    - `Zikula\BlocksModule\Api\ApiInterface\BlockApiInterface` has dropped `getModuleBlockPath()` method.
    - `Zikula\BlocksModule\Api\ApiInterface\BlockFactoryApiInterface` has changed signature of `getInstance()` method.
    - `Zikula\BlocksModule\BlockHandlerInterface` requires a new method `getPropertyDefaults()` to be implemented.
    - `Zikula\Bundle\HookBundle\HookProviderInterface` requires a new method `getAreaName()` to be implemented.
    - `Zikula\Bundle\HookBundle\HookSubscriberInterface` requires a new method `getAreaName()` to be implemented.
    - `Zikula\Bundle\HookBundle\HookProviderInterface` has dropped `setServiceId` and `getServiceId` methods.
    - `Zikula\Bundle\HookBundle\Collector\HookCollectorInterface` has changed signature of `addProvider()` and `addSubscriber()` methods.
    - `Zikula\Common\Content\ContentTypeInterface` requires a new method `getBundleName()` to be implemented.
    - `Zikula\PermissionsModule\Entity\RepositoryInterface\PermissionRepositoryInterface` requires new methods `getAllColours()` and `deleteGroupPermissions()` to be implemented.
    - `Zikula\ŞearchModule\SearchableInterface` requires a new method `getBundleName()` to be implemented.
    - `Zikula\ŞearchModule\SearchableInterface` has changed signature of `getResults()` method.        
    - `Zikula\UsersModule\MessageModule\MessageModuleInterface` requires a new method `getBundleName()` to be implemented.
    - `Zikula\UsersModule\ProfileModule\ProfileModuleInterface` requires a new method `getBundleName()` to be implemented.
  - `Zikula\BlocksModule\AbstractBlockHandler` is not ContainerAware anymore.
  - `Zikula\ExtensionsModule\Installer\AbstractExtensionInstaller` is not ContainerAware anymore.
  - Entity changes
    - `Zikula\BlocksModule\Entity\BlockEntity` changed some obsolete accessors for PSR-1 compatibility. Please use now `getLastUpdate/setLastUpdate`.
    - `Zikula\CategoriesModule\Entity\CategoryEntity` changed some obsolete accessors for PSR-1 compatibility. Please use now `getLocked/setLocked`, `getLeaf/setLeaf`, `getDisplayName/setDisplayName`, `getDisplayDesc/setDisplayDesc`, `getCreatedDate/setCreatedDate`, `getUpdatedDate/setUpdatedDate`, `getCreatedBy/setCreatedBy`, `getUpdatedBy/setUpdatedBy`.
    - `Zikula\CategoriesModule\Entity\CategoryRegistryEntity` removed some obsolete accessors for PSR-1 compatibility. Please use now `getStatus/setStatus`, `getCreatedDate/setCreatedDate`, `getUpdatedDate/setUpdatedDate`, `getCreatedBy/setCreatedBy`, `getUpdatedBy/setUpdatedBy`.
    - `Zikula\ExtensionsModule\Entity\ExtensionEntity` has renamed `core_min` to `coreCompatibility` and removed `core_max` property (#3649).
      - The table name has been renamed from `modules` to `extensions`.
    - `Zikula\PermissionsModule\Entity\PermissionEntity` removed the `realm` and `bond` properties.
    - `Zikula\ThemeModule\Entity\ThemeEntity` is removed along with its Repository and RepositoryInterface classes.
      - The data is now stored in the `extensions` table and managed by the ExtensionsModule.
    - `Zikula\SearchModule\Entity\SearchResultEntity` has changed the `extra` field from `text` to `array`. The `setExtra()` method takes care of that though.
    - `Zikula\UsersModule\Entity\UserEntity` changed some obsolete accessors for PSR-1 compatibility. Please use now `getApprovedDate/setApprovedDate`, `getApprovedBy/setApprovedBy`, `getRegistrationDate/setRegistrationDate`, `getLastLogin/setLastLogin`.
    - `Zikula\ZAuthModule\Entity\UserVerificationEntity` changed some obsolete accessors for PSR-1 compatibility. Please use now `getCreatedDate/setCreatedDate`.
  - Removed `Zikula\Core\Response\Ajax\*Response` classes (#3772). Use Symfony's `JsonResponse` with appropriate status codes instead.
  - Removed all classes from the `Zikula\Core\Token` namespace. If you need custom CSRF tokens use [isCsrfTokenValid()](https://symfony.com/doc/current/security/csrf.html#generating-and-checking-csrf-tokens-manually) instead (#3206).
  - The `Zikula\Bundle\HookBundle\ServiceIdTrait` trait has been removed.
  - The `Zikula\SettingsModule\Validator\ValidController*` classes have been removed.
  - `CoreBundle/Composer/Metadata` has removed `$basePath` and `$rootPath` properties and their getters.
  - `$kernel::isCoreModule()` is renamed to `$kernel::isCoreExtension()`.
    - The corresponding Twig function is similarly renamed.
  - `Zikula\ExtensionsModule\Event\ModuleStateEvent` is renamed to `Zikula\ExtensionsModule\Event\ExtensionStateEvent`.
    - Its methods also renamed: `getModule` -> `getExtension` and `getModInfo` -> `getInfo`.
  - All the Events in `Zikula\ExtensionsModule\ExtensionEvents` are changed - both the name and the ConstantName.
  - `Zikula\ZAuthModule\Api\PasswordApi` & `Zikula\ZAuthModule\Api\ApiInterface\PasswordApiInterface` are deprecated and will be removed in Core-4.0.0
    - Use `Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface` or `bin2hex(random_bytes(8))`
  - Dropped vendors:
    - Removed afarkas/html5shiv
    - Removed afarkas/webshim (#3925)
    - Removed bootstrap-plus/bootstrap-jqueryui (use jQuery UI directly)
    - Removed doctrine/doctrine-cache-bundle (in favour of [Symfony/Cache](https://symfony.com/doc/current/components/cache.html))
    - Removed elao/web-profiler-extra-bundle
    - Removed jms/translation-bundle (in favour of php-translation/*)
    - Removed oyejorge/less.php
    - Removed ramsey/array_column
    - Removed sensio/distribution-bundle (in favour of Flex)
    - Removed sensio/generator-bundle (in favour of symfony/maker-bundle)
    - Removed symfony/polyfill-apcu
    - Removed symfony/polyfill-php56
    - Removed symfony/polyfill-php70
    - Removed symfony/polyfill-util
    - Removed twig/extensions (use new Twig Extra extensions instead)
    - Removed zikula/andreas08-theme (in favour of different styles in bootstrap theme)
    - Removed zikula/bootstrap-bundle (in favour of direct usage of components/bootstrap)
    - Removed zikula/fontawesome-bundle (in favour of direct usage of components/font-awesome)
    - Removed zikula/generator-bundle (in favour of symfony/maker-bundle)
    - Removed zikula/jquery-bundle (in favour of direct usage of components/jquery)
    - Removed zikula/jquery-ui-bundle (in favour of direct usage of components/jqueryui)
    - Removed zikula/seabreeze-theme (in favour of different styles in bootstrap theme)
    - Replaced by stof/doctrine-extensions-bundle by antishov/doctrine-extensions-bundle
    - Downgraded kriswallsmith/assetic from 1.4.0 to 1.0.5
  - Removed `Zikula\Core\Exception\FatalErrorException` in favour of direct usage of `Symfony\Component\ErrorHandler\Error\FatalError`
  - Removed the `polyfill` Twig tag (#3925).
  - Removed the `languageName` Twig filter (use `language_name` instead ([docs](https://twig.symfony.com/doc/3.x/filters/language_name.html)))
  - Removed `ZikulaKernel::VERSION_SUB` constant.
  - `Bundle\CoreBundle\Helper\PersistedBundleHelper::getConnection` visibility set to private
  - `Bundle\CoreBundle\Helper\PersistedBundleHelper::addAutoloaders` visibility set to private
  - `Bundle\CoreBundle\Helper\BundlesSchemaHelper::createSchema` visibility set to private
  - There is no `web/bootstrap-font-awesome.css` file generated anymore. Instead, Bootstrap and Font Awesome are always included independently.
  - Removed custom translation system (#4042). Use Symfony's translation system directly.
    - Default translation domain is now always `messages`. Use specific other domains (e.g. `mail`, `config`, `hooks` etc.) where appropriate.
  - Replaced `LinkContainer` with `ExtensionMenu` for collecting module menus (admin, user, account). See companion docs.
  - Changes to `composer.json`
    - Removed use of `admin.png` and replaced by adding icon class >> `extra/zikula/icon: "fas fa-user"`.
      - Themes now also need to include an icon.
    - Setting >> `extra/zikula/capabilities/admin/url` is no longer supported. Use `extra/zikula/capabilities/admin/route`.
    - Change how themes define user and admin capabilities.
      - old: e.g. `capabilities/admin:true`
      - new: e.g. `capabilities/admin/theme:true`
  - Changes regarding directory layout
    - Non-core themes and modules are now _both_ stored in `src/extensions`.
    - The `src/app/config/` directory has been moved to `config/`.
    - The `src/app/Resources/<BundleName>/views/` directory is now located at `templates/bundles/<BundleName>/`.
    - The `src/app/Resources/translations/` directory became `translations/`.
    - The `src/app/Resources/views/` directory became `templates/`.
    - The `src/app/Resources/workflows/` directory became `config/workflows/`.
    - The `src/lib/Zikula/Bundle/` directory has been moved to `src/Zikula/`.
    - The `src/web/` directory has been moved to `public/`.
  - Changes regarding configuration files
    - Configuration for specific packages has been moved into `config/packages/*.yaml`.
    - The `parameters.yml` file has been renamed to `services.yaml`.
    - The `custom_parameters.yaml` file has been renamed to `services_custom.yaml`.
    - YAML files use the `.yaml` extension instead of `.yml`.
    - The `%temp_dir%` parameter has been removed. If you need a temporary folder use `sys_get_temp_dir()`.
    - The parameters `system.chmod_dir` and `url_secret` have been removed without any replacement.
    - Some other parameter have been removed in favour of environment variables
      - `env` became `APP_ENV`.
      - `debug` became `APP_DEBUG`.
      - `secret` became `APP_SECRET`.
      - `database_*` became `DATABASE_URL`.

- Fixes:
  - Check if verification record is already deleted when confirming a changed mail address.
  - Updated listener priorities in Settings module to fix non-working variable localisation (#3934).
  - Fixed broken functionality of hiding submit button in search block.
  - Provide more kernel information in coredata (#3651).
  - Cosmetical corrections for account link graphics.
  - Properly consider "user must verify" flag during user creation in ZAuth module (#3964).
  - Removed workaround for older DBAL versions (#2185).
  - Properly handle deleted user groups in permissions module (#3963).
  - Made Blocks module's JavaScript functionality more robust (#3911).
  - Removed ancient workaround in printer theme (#3653).
  - Readded missing functionality for configurable page title schemes (#3921).
  - Readded missing permission checks for specific admin area categories.
  - Fixed behaviour of recent searches list.
  - Fixed admin notification email for new registrations which was not done in some cases.
  - Improved asset merger with regards to negative weights (#3978).
  - Fixed broken JavaScript in ZAuth user modification form (#3992).
  - Fixed "remember me" problem caused by faulty session regeneration with custom lifetime in PHP 7.2+ (#3898, #4078).
  - When updating a block, orphan properties are removed (#3892).
  - Refactored page title handling, introducing a new `\Zikula\Bundle\CoreBundle\Site\SiteDefinitionInterface` (#3969).
  - Fixed creating new ZAuth users as admin without setting a password.
  - Start page controllers now get properly set the `_route` request argument (#3955).
  - Default minimum length for passwords is now raised to 8. Absolute minimum length is still 5 (#2842).
  - Set correct port for Gmail transport type (#4142).
  - Fixed broken drag n drop of categories and menu items when target position is the top of a subtree.
  - Fixed logic of `CategoryProcessingHelper#mayCategoryBeDeletedOrMoved` (#3920).

- Features:
  - Utilise autowiring and autoconfiguring functionality from Symfony (#3940).
  - Migrated all templates to Bootstrap 4 and Font Awesome 5 (#3530, #4037).
  - Added all styles from Bootswatch to the Bootstrap theme (#4037).
  - Added option to allow users individually switching between available Bootswatch styles (#4037).
  - Centralised dynamic form field handling from Profile module in FormExtensionBundle (#3945).
  - Allow zasset syntax for relative assets also for normal bundles.
  - Added Twig function for creating a `RouteUrl` instance (#3802).
  - Added support for separators in dropdown menus of extensions interface / module links (#3904).
  - Added common header/footer templates for login templates (#3937).
  - Added common header/footer templates for user registration and login related email templates (#3937).
  - Reworked `Zikula\Bridge\HttpFoundation\DoctrineSessionHandler` to extend `Symfony\Component\HttpFoundation\Session\Storage\Handler\AbstractSessionHandler` (#3870).
  - Support arrays and longer strings in the `extra` field of search results (#3619, #3900).
  - More user-friendly response messages during account information recovery (#3723).
  - Scalar type hints have been added to all method arguments and return values; corresponding docblocks have been dropped (#3960).
  - Added CLI Commands to manage extension installation, upgrade and uninstall and sync (#3517).
  - Added ability to choose a Font Awesome icon for admin categories, categories and extensions (#3598, #4061).
  - Added support for creating and changing translations on-site using "Edit in Place" and/or a WebUI (#4012, #2425).
  - `LocaleApi` is now able to work with regions, too (#4012, #2425).
  - New and removed locales are automatically reflected in the configuration (#4012, #2425).
  - Added possibility to specify custom database port in installer.
  - Blocks can now specify default property defaults used for custom form fields (#3676).
  - Added twig-inspector for easy debugging of Twig templates (#4051).
  - Added new fields for optional comments and colours to permission rules (#914).
  - In general, 'module' and 'theme' are now generically referred to as 'extensions' and many methods or properties have been renamed to align.
  - The location for choosing the default theme and admin theme has been moved to the Theme module settings.
  - System themes (Bootstrap, Atom, Printer, Rss) are now located in `system/` and are loaded directly into the kernel.
  - Added ability to create dynamic site properties (e.g. titles, meta descriptions etc.) by subclassing `Zikula\Bundle\CoreBundle\Site\SiteDefinition` (#519).
  - If a record is not found for a guest this is now similarly treated like an access denied exception (redirect to login form).
  - Persist the locale a user used during his registration (#4098).
  - Start page can now be defined much easier (a dropdown allows to choose a route/controller combination) (#3955).
  - Start page arguments can now be defined more flexible (GET parameters and request attributes) (#3955).
  - Start page can now be configured for each available language (#3955).
  - Passwords in the ZAuth module are now always hashed with the the most up-to-date algorithm available (via Symfony security component) and automatically updated on login (#2842).
  - Passwords can optionally be validated with Symfony's NonCompromisedPassword validator ([docs](https://symfony.com/doc/current/reference/constraints/NotCompromisedPassword.html)) (#2842).
  - A new password strength meter is implemented (see [GitHub repo](https://github.com/ablanco/jquery.pwstrength.bootstrap)) (#2842).
  - Added a simple password generator in all places where a new password might be needed (#2842).
  - Added ability to force a group of users to change their password on next login (#2842).
  - Extensions module automatically contributes admin menu item to display Markdown docs for other extensions. Help UI can be configured to use either a modal window or a fixed sidebar (#3739).
  - Added "Connections" menu to ExtensionsMenu so extensions can add menu children to other connected extension's admin UI.
  - Added @PermissionCheck annotation for use in Controllers. See `Zikula\PermissionsModule\Annotation\PermissionCheck` and examples in Core.

- Vendor updates:
  - antishov/doctrine-extensions-bundle updated from 1.2.2 to 1.4.2
  - behat/transliterator updated from 1.2.0 to 1.3.0
  - components/bootstrap updated from 3.4.1 to 4.4.1
  - components/font-awesome updated from 4.7.0 to 5.12.1
  - composer/ca-bundle updated from 1.2.4 to 1.2.6
  - composer/composer installed in 1.10.0-RC
  - composer/installers updated from 1.7.0 to 1.8.0
  - composer/semver updated from 1.5.0 to 1.5.1
  - composer/spdx-licenses installed in 1.5.3
  - composer/xdebug-handler installed in 1.4.0
  - doctrine/annotations updated from 1.2.7 to 1.8.0
  - doctrine/collections updated from 1.3.0 to 1.6.4
  - doctrine/common updated from 2.6.2 to 2.12.0
  - doctrine/dbal updated from 2.5.13 to 2.10.1
  - doctrine/doctrine-bundle updated from 1.6.13 to 2.0.7
  - doctrine/event-manager installed in 1.1.0
  - doctrine/inflector updated from 1.1.0 to 1.3.1
  - doctrine/instantiator updated from 1.0.5 to 1.3.0
  - doctrine/lexer updated from 1.0.2 to 1.2.0
  - doctrine/orm updated from 2.5.14 to 2.7.1
  - doctrine/persistence installed in 1.3.6
  - doctrine/reflection installed in 1.1.0
  - egulias/email-validator installed in 2.1.17
  - erusev/parsedown installed in 1.7.4
  - friendsofsymfony/jsrouting-bundle updated from 1.6.3 to 2.5.3
  - gedmo/doctrine-extensions updated from 2.4.37 to 2.4.39
  - guzzlehttp/guzzle updated from 6.4.1 to 6.5.2
  - imagine/imagine updated from 0.7.1 to 1.3.3
  - itsjavi/fontawesome-iconpicker installed in 3.2.0
  - jms/i18n-routing-bundle updated from 2.0.0 to 3.0.3 (temporarily using remmel/i18n-routing-bundle dev-master instead)
  - jquery.mmenu updated from 7.3.3 to mmenu.js 8.4.7
  - justinrainbow/json-schema updated from 4.1.0 to 5.2.9
  - knplabs/knp-menu updated from 2.2.0 to 3.1.0
  - knplabs/knp-menu-bundle updated from 2.1.3 to 3.0.0
  - league/commonmark installed in 1.3.0
  - league/html-to-markdown installed in 4.9.1
  - liip/imagine-bundle updated from 1.9.1 to 2.3.0
  - lorenzo/pinky installed in 1.0.5
  - matthiasnoback/symfony-console-form updated from 2.3.0 to 3.6.0 (temporarily using Jeroeny/dev-symfony-5 instead)
  - michelf/php-markdown updated from 1.7.0 to 1.9.0
  - monolog/monolog updated from 1.25.2 to 2.0.2
  - nikic/php-parser updated from 1.4.1 to 4.3.0
  - nyholm/nsa installed in 1.1.0
  - ocramius/package-versions installed in 1.4.2
  - oro/twig-inspector installed in 1.0.2
  - paragonie/random_compat updated from 2.0.18 to 9.99.99
  - php-translation/common installed in 3.0.1
  - php-translation/extractor installed in 2.0.1
  - php-translation/symfony-bundle installed in 0.12.0 (temporarily using dev-master)
  - php-translation/symfony-storage installed in 2.2.0
  - psr/event-dispatcher installed in 1.0.0
  - ralouphie/getallheaders updated from 2.0.5 to 3.0.3
  - seld/jsonlint installed in 1.7.2
  - seld/phar-utils installed in 1.1.0
  - sensio/framework-extra-bundle updated from 3.0.29 to 5.5.3
  - sensiolabs/security-checker updated from 5.0.3 to 6.0.3
  - swiftmailer/swiftmailer updated from 5.4.12 to 6.2.3
  - symfony/contracts installed in 2.0.1
  - symfony/maker-bundle installed in 1.14.3
  - symfony/monolog-bundle updated from 3.2.0 to 3.5.0
  - symfony/phpunit-bridge updated from 3.4.14 to 5.0.4
  - symfony/polyfill-ctype updated from 1.12.0 to 1.14.0
  - symfony/polyfill-iconv installed in 1.14.0
  - symfony/polyfill-intl-grapheme installed in 1.14.0
  - symfony/polyfill-intl-icu updated from 1.11.0 to 1.14.0
  - symfony/polyfill-intl-idn updated from 1.11.0 to 1.14.0
  - symfony/polyfill-intl-messageformatter installed in 1.14.0
  - symfony/polyfill-intl-normalizer installed in 1.14.0
  - symfony/polyfill-mbstring updated from 1.12.0 to 1.14.0
  - symfony/polyfill-php72 installed in 1.14.0
  - symfony/polyfill-php73 installed in 1.14.0
  - symfony/profiler-pack installed in 1.0.4
  - symfony/swiftmailer-bundle updated from 2.4.3 to 3.4.0
  - symfony/symfony updated from 3.4.35 to 5.0.4
  - thomaspark/bootswatch installed in 4.4.1
  - tijsverkoyen/css-to-inline-styles installed in 2.2.2
  - twig/extra-bundle installed in 3.0.3
  - twig/cssinliner-extra installed in 3.0.3
  - twig/html-extra installed in 3.0.3
  - twig/inky-extra installed in 3.0.3
  - twig/intl-extra installed in 3.0.3
  - twig/markdown-extra installed in 3.0.3
  - twig/string-extra installed in 3.0.3
  - twig/twig updated from 1.42.4 to 3.0.3
  - vakata/jstree updated from 3.3.8 to 3.3.9
  - webmozart/assert updated from 1.5.0 to 1.7.0
  - willdurand/js-translation-bundle updated from 2.6.6 to 3.0.1
  - zikula/legal-module updated from 3.1.2 to dev-master
  - zikula/oauth-module updated from 1.0.4 to dev-master
  - zikula/pagelock-module updated from 1.2.3 to dev-master
  - zikula/profile-module updated from 3.0.6 to dev-master
  - zikula/wizard updated from 2.0 to 3.0.3
