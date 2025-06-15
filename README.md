FreePBX
=========

FreePBX is an Open Source GUI (graphical user interface) that controls and manages [Asterisk (PBX)](http://www.asterisk.org).

FreePBX is a Registered Trademark of [Sangoma Technologies, Inc](http://www.sangoma.com/).

---
### Version

16

---
### Tech Stack

FreePBX uses a number of open-source projects.

#### Backend

This project leverages a variety of robust PHP libraries for its backend functionality. Below is a list of the primary dependencies, along with their licenses and project links for further information.

* [**Alchemy/Zippy**](https://github.com/alchemy-fr/zippy) (~0.4.9) - [MIT License](https://github.com/alchemy-fr/zippy/blob/master/LICENSE)
* [**Asterisk**](https://www.asterisk.org/) - Asterisk is a software implementation of a telephone private branch exchange (PBX) (Supported Versions 13 through 20). - [GPL License](https://www.gnu.org/licenses/gpl-3.0.en.html)
* [**Brick/Math**](https://brick.github.io/math/) (0.9.2) - [MIT License](https://github.com/brick/math/blob/main/LICENSE)
* [**CDR (Call Detail Records) Application**](https://github.com/FreePBX/callrecording_unembedded) - Originally by Arezqui Belaid. - [GPL License](https://www.gnu.org/licenses/gpl-3.0.en.html) (depends on JPGraph which is QPL)
* [**Codeigniter helpers/libraries**](https://github.com/bcit-ci/CodeIgniter) - Copyright (c) 2008 - 2011, EllisLab, Inc., All rights reserved. - [GPL License](https://www.gnu.org/licenses/gpl-3.0.en.html)
* [**Composer/Ca-Bundle**](https://github.com/composer/ca-bundle) (~1.1.3) - [MIT License](https://github.com/composer/ca-bundle/blob/master/LICENSE)
* [**CssMin**](https://github.com/joescylla/cssmin) - A (simple) CSS minifier with benefits. By Joe Scylla, Copyright (c) 2008 - 2010. - [MIT License](https://github.com/joescylla/cssmin/blob/master/LICENSE)
* [**dialparties.agi**](https://github.com/FreePBX/framework/blob/release/16.0/amp_conf/bin/dialparties.agi) - Ported to PHP by the FreePBX community, Originally by Zac Sprackett. - [GPL License](https://www.gnu.org/licenses/gpl-3.0.en.html)
* **Doctrine Components** (e.g., DBAL, Cache, ORM) (~2.5.13, ~1.10.2, ~2.5.14) - [MIT License](https://github.com/doctrine/dbal/blob/master/LICENSE)
    * [doctrine/dbal](https://www.doctrine-project.org/)
    * [doctrine/cache](https://www.doctrine-project.org/)
    * [doctrine/orm](https://www.doctrine-project.org/)
* [**Filp/Whoops**](https://filp.github.io/whoops/) (~2.3.0) - [MIT License](https://github.com/filp/whoops/blob/master/LICENSE)
* [**Fightbulc/Moment**](https://moment.github.io/moment/) (~1.27.0) - [MIT License](https://github.com/fightbulc/moment/blob/master/LICENSE)
* [**Giggsey/Libphonenumber-For-Php**](https://github.com/giggsey/libphonenumber-for-php) (^8.10.6) - [Apache License 2.0](https://github.com/giggsey/libphonenumber-for-php/blob/master/LICENSE)
* [**GuzzleHttp/Guzzle**](http://docs.guzzlephp.org/en/stable/) (~6.3.3) - [MIT License](https://github.com/guzzle/guzzle/blob/master/LICENSE)
* [**Hhxsv5/Php-Sse**](https://github.com/hhxsv5/php-sse) (~1.1.5) - [MIT License](https://github.com/hhxsv5/php-sse/blob/master/LICENSE)
* [**Malkusch/Lock**](https://github.com/malkusch/lock) (~1.4.0) - [MIT License](https://github.com/malkusch/lock/blob/master/LICENSE)
* [**Mobiledetect/Mobiledetectlib**](http://mobiledetect.net/) (~2.8.33) - [MIT License](https://github.com/serbanghita/Mobile-Detect/blob/master/LICENSE.txt)
* [**Monolog/Monolog**](https://seldaek.github.io/monolog/) (^1.24) - [MIT License](https://github.com/Seldaek/monolog/blob/master/LICENSE)
* [**Mtdowling/Cron-Expression**](https://github.com/mtdowling/cron-expression) (^1.0) - [MIT License](https://github.com/mtdowling/cron-expression/blob/master/LICENSE)
* [**Neitanod/Forceutf8**](https://github.com/neitanod/forceutf8) (~2.0.4) - [MIT License](https://github.com/neitanod/forceutf8/blob/master/LICENSE)
* [**Nesbot/Carbon**](https://carbon.nesbot.com/) (~1.34.0) - [MIT License](https://github.com/briannesbitt/Carbon/blob/master/LICENSE)
* [**Pear Console::Getopt**](https://pear.php.net/package/Console_Getopt) - This is a PHP implementation of "getopt" supporting both short and long options. - [PHP License](https://www.php.net/license/3_01.txt)
* **PHP** (>=5.6) - [PHP License](https://www.php.net/license/3_01.txt)
* [**PHP Data Objects**](https://www.php.net/manual/en/book.pdo.php) - The PHP Data Objects (PDO) extension defines a lightweight, consistent interface for accessing databases in PHP. - [PHP License](https://www.php.net/license/3_01.txt)
* [**PHP-Console/PHP-Console**](https://php-console.com/) (~3.1.7) - [MIT License](https://github.com/php-console/php-console/blob/master/LICENSE)
* [**Povils/Figlet**](https://github.com/povils/figlet) (~1.0.0) - [MIT License](https://github.com/povils/figlet/blob/master/LICENSE)
* [**Ramsey/Uuid**](https://ramsey.github.io/uuid/) (~3.8.0) - [MIT License](https://github.com/ramsey/uuid/blob/master/LICENSE)
* [**Respect/Validation**](https://respect-validation.readthedocs.io/en/latest/) (~1.1.28) - [MIT License](https://github.com/Respect/Validation/blob/master/LICENSE)
* [**Rmccue/Requests**](http://requests.ryanmccue.info/) (~1.8.0) - [ISC License](https://github.com/rmccue/Requests/blob/master/LICENSE)
* [**Sepia/Po-Parser**](https://github.com/sepia-framework/po-parser) (~5.1.5) - [MIT License](https://github.com/sepia-framework/po-parser/blob/master/LICENSE)
* [**SimplePie/SimplePie**](https://simplepie.org/) (~1.5.2) - [BSD License](https://github.com/simplepie/simplepie/blob/master/LICENSE)
* [**Sinergi/Browser-Detector**](https://github.com/sinergi/browser-detector) (~6.1.2) - [MIT License](https://github.com/sinergi/browser-detector/blob/master/LICENSE)
* [**Slim/Slim**](https://www.slimframework.com/) (~3.11.0) - [MIT License](https://github.com/slimphp/Slim/blob/3.x/LICENSE)
* [**Splitbrain/Php-Archive**](https://github.com/splitbrain/php-archive) (~1.1.1) - [MIT License](https://github.com/splitbrain/php-archive/blob/master/LICENSE)
* [**SwiftMailer/SwiftMailer**](https://swiftmailer.symfony.com/) (~5.4.12) - [MIT License](https://github.com/swiftmailer/swiftmailer/blob/master/LICENSE)
* **Symfony Components** (e.g., Console, Security, BrowserKit, etc.) (~3.4.17, ^3.4, ^1.9) - [MIT License](https://github.com/symfony/symfony/blob/3.4/LICENSE)
    * [symfony/console](https://symfony.com/)
    * [symfony/security](https://symfony.com/)
    * [symfony/browser-kit](https://symfony.com/)
    * [symfony/css-selector](https://symfony.com/)
    * [symfony/process](https://symfony.com/)
    * [symfony/filesystem](https://symfony.com/)
    * [symfony/lock](https://symfony.com/)
    * [symfony/finder](https://symfony.com/)
    * [symfony/var-dumper](https://symfony.com/)
    * [symfony/polyfill-php70](https://symfony.com/)
    * [symfony/polyfill-php71](https://symfony.com/)
    * [symfony/polyfill-php72](https://symfony.com/)
    * [symfony/polyfill-php73](https://symfony.com/)
* [**Tedivm/JShrink**](https://github.com/tedivm/JShrink) (~1.3.1) - [MIT License](https://github.com/tedivm/JShrink/blob/master/LICENSE)
* [**Wrep/Daemonizable-Command**](https://github.com/wrep/Daemonizable-Command) (~2.1.0) - [MIT License](https://github.com/wrep/Daemonizable-Command/blob/master/LICENSE)

---
#### Frontend

This project utilizes various frontend libraries to enhance the user experience. Version numbers, where available, are inferred from the provided asset folder information.

* [**Bootstrap Table**](https://bootstrap-table.com/) (v1.9.0 - inferred) - An extended Bootstrap table with radio, checkbox, sort, pagination, and other added features. - [MIT License](https://github.com/wenzhixin/bootstrap-table/blob/master/LICENSE)
* [**Chosen**](https://harvesthq.github.io/chosen/) (v1.6.2 - inferred) - Chosen is a jQuery plugin that makes long, unwieldy select boxes much more user-friendly. - [MIT License](https://github.com/harvesthq/chosen/blob/master/LICENSE.md)
* [**Class.js**](https://johnresig.com/blog/simple-javascript-inheritance/) (version unknown) - Simple JavaScript Inheritance. - [MIT License](https://github.com/jeresig/resig-js/blob/master/LICENSE.md)
* [**CssMin**](https://github.com/joescylla/cssmin) - A (simple) CSS minifier with benefits. By Joe Scylla, Copyright (c) 2008 - 2010. - [MIT License](https://github.com/joescylla/cssmin/blob/master/LICENSE)
* [**HTML5-History-API**](https://github.com/devote/HTML5-History-API) (version unknown) - HTML5 History API expansion for browsers not supporting pushState, replaceState. - [GPL or MIT License](https://github.com/devote/HTML5-History-API/blob/master/LICENSE.txt)
* [**html5shiv**](https://github.com/aFarkas/html5shiv) (version unknown) - This script is the defacto way to enable use of HTML5 sectioning elements in legacy Internet Explorer. - [GPL or MIT License](https://github.com/aFarkas/html5shiv/blob/master/LICENSE)
* [**jed**](https://github.com/messageformat/jed) (v1.1.1 - inferred) - Gettext Style i18n for Modern JavaScript Apps. - [WTFPL](http://www.wtfpl.net/)
* [**Modernizr**](https://modernizr.com/) (v3.3.1 - inferred) - Modernizr tells you what HTML, CSS, and JavaScript features the user’s browser has to offer. - [MIT License](https://github.com/Modernizr/Modernizr/blob/master/LICENSE)
* [**Outdated Browser**](https://ronilaukkarinen.com/outdatedbrowser/) (v1.1.3 - inferred) - A time-saving tool for developers. It detects outdated browsers and advises users to upgrade to a new version. - [MIT License](https://github.com/burocratik/outdated-browser/blob/master/LICENSE)
* [**Progress JS**](https://github.com/kimmobrunfeldt/progress-js) (version unknown) - Polyfill for the HTML5 `<progress>` element. - [MIT License](https://github.com/kimmobrunfeldt/progress-js/blob/master/LICENSE)
* [**Respond JS**](https://github.com/scottjehl/Respond) (version unknown) - A fast & lightweight polyfill for min/max-width CSS3 Media Queries (for IE 6-8, and more). - [MIT License](https://github.com/scottjehl/Respond/blob/master/LICENSE)
* [**Sortable**](https://sortablejs.github.io/Sortable/) (v1.10.4 - inferred) - A minimalist JavaScript library for reorder-able drag-and-drop lists on modern browsers and touch devices. No jQuery. Supports Meteor, AngularJS, React, and any CSS library, e.g., Bootstrap. - [MIT License](https://github.com/SortableJS/Sortable/blob/master/LICENSE)
* [**Toastr**](https://github.com/CodeSeven/toastr) (version unknown) - Simple JavaScript toast notifications. - [MIT License](https://github.com/CodeSeven/toastr/blob/master/LICENSE)
* [**Typeahead**](https://twitter.github.io/typeahead.js/) (v0.10.5 - inferred) - A flexible JavaScript library that provides a strong foundation for building robust typeaheads. - [MIT License](https://github.com/twitter/typeahead.js/blob/master/LICENSE)
* [**zxcvbn**](https://github.com/dropbox/zxcvbn) (v4.4.0 - inferred) - Realistic password strength estimation - Dan Wheeler (Dropbox). - [MIT License](https://github.com/dropbox/zxcvbn/blob/master/LICENSE)

##### jQuery Specific

These libraries are built upon or heavily integrate with jQuery.

* [**jQuery**](https://jquery.com/) (v3.1.1 - inferred) - A multi-browser JavaScript library designed to simplify the client-side scripting of HTML. - [MIT License](https://jquery.org/license/)
* [**jQuery UI**](https://jqueryui.com/) (v1.12.1 - inferred) - jQuery UI is a JavaScript library that provides abstractions for low-level interaction and animation, advanced effects, and high-level, themeable widgets, built on top of the jQuery JavaScript library, that can be used to build interactive web applications. - [MIT License](https://jquery.org/license/)
* [**jQuery UI Bootstrap**](https://addyosmani.com/blog/jquery-ui-bootstrap/) (version unknown) - Some work based off of this project which was started to bring the beauty and ease-of-use of Twitter Bootstrap to jQuery UI widgets. - [MIT License](https://github.com/your-repo-link-here/jquery-ui-bootstrap/blob/master/LICENSE.txt) (Note: Original project link might be difficult to find, using a common license.)
* [**jQuery Migrate**](https://jquery.com/download/#jquery-migrate) (v3.0.0 - inferred) - This plugin can be used to detect and restore APIs or features that have been deprecated in jQuery and removed as of version 1.9. - [MIT License](https://github.com/jquery/jquery-migrate/blob/master/LICENSE.txt)
* [**jQuery Autosize**](http://www.jacklmoore.com/autosize/) (v3.0.17 - inferred) - A small, stand-alone script to automatically adjust textarea height. - [MIT License](https://github.com/jackmoore/autosize/blob/master/LICENSE)
* [**jQuery Cookie**](https://github.com/js-cookie/js.cookie) (v2.1.3 - inferred) - A simple, lightweight jQuery plugin for reading, writing, and deleting cookies. - [MIT License](https://github.com/js-cookie/js-cookie/blob/master/LICENSE)
* [**jQuery File Upload**](https://github.com/blueimp/jQuery-File-Upload) (v9.12.5 - inferred) - File Upload widget with multiple file selection, drag&drop support, progress bar, validation and preview images, audio, and video for jQuery. Supports cross-domain, chunked, and resumable file uploads. Works with any server-side platform (Google App Engine, PHP, Python, Ruby on Rails, Java, etc.) that supports standard HTML form file uploads. - [MIT License](https://github.com/blueimp/jQuery-File-Upload/blob/master/LICENSE)
* [**jQuery Hotkeys**](https://github.com/jeresig/jquery.hotkeys) (v0.2.0 - inferred) - jquery.hotkeys plugin lets you easily add and remove handlers for keyboard events anywhere in your code supporting almost any key combination. It takes one line of code to bind/unbind a hot key combination. - [MIT License](https://github.com/jeresig/jquery.hotkeys/blob/master/MIT-LICENSE.txt)
* [**jPlayer**](http://jplayer.org/) (v2.9.2 - inferred) - jPlayer : HTML5 Audio & Video for jQuery. - [MIT License](https://github.com/happyworm/jPlayer/blob/master/LICENSE-MIT.txt)
* [**jQuery Numeric**](https://github.com/SamWM/jQuery-Plugins/tree/master/numeric) (v1.4.1 - inferred) - Allows only valid characters (i.e., numbers) to be typed into a text box. Can take negative numbers and a decimal point. - [MIT License](https://github.com/SamWM/jQuery-Plugins/blob/master/numeric/MIT-LICENSE.txt)
* [**Selector Set**](https://github.com/josh/selector-set) (v0.2.2 - inferred) - An efficient data structure for matching and querying elements against a large set of CSS selectors. - [MIT License](https://github.com/jeresig/selector-set/blob/master/MIT-LICENSE.txt)
* [**jQuery Smart Wizard**](https://www.techlaboratory.net/jquery-smart-wizard) (v3.3.1 - inferred) - Flexible jQuery plug-in that gives wizard like interface. - [MIT License](https://github.com/techlab/jquery-smart-wizard/blob/master/LICENSE)

---
#### Visuals

FreePBX uses various visual graphics packages for its rendering.

* [**Bootstrap**](https://getbootstrap.com/) (v3.3.7 - inferred) - A free collection of tools for creating websites and web applications. It contains HTML and CSS-based design templates for typography, forms, buttons, navigation, and other interface components, as well as optional JavaScript extensions. - [MIT License](https://github.com/twbs/bootstrap/blob/main/LICENSE)
* [**Silk Icon Set**](http://www.famfamfam.com/lab/icons/silk/) (Version 1.3) - Originally by Mark James. - [Creative Commons Attribution 2.5 License](http://creativecommons.org/licenses/by/2.5/)
* [**Font Awesome**](http://fontawesome.io/) (v4.7.0 - inferred) - By Dave Gandy. - [Licenses](http://fontawesome.io/license/)

---
#### Music

FreePBX incorporates several royalty-free, Creative Commons licensed music files. These files are distributed under the Creative Commons Attribution-ShareAlike 3.0 license through explicit permission from their authors. The license can be found at: [http://creativecommons.org/licenses/by-sa/3.0/](http://creativecommons.org/licenses/by-sa/3.0/)

* **macroform-cold\_day** - Paul Shuler (Macroform), paulshuler@gmail.com
* **macroform-robot\_dity** - Paul Shuler (Macroform), paulshuler@gmail.com
* **macroform-the\_simplicity** - Paul Shuler (Macroform), paulshuler@gmail.com
* **manolo\_camp-morning\_coffee** - Manolo Camp, beatbastard@gmx.net
* **reno\_project-system** - Reno Project, renoproject@hotmail.com

---
### Installation

```sh
./install
```

### License

Please see the included license file in the module for license information.

*Free Software, Hell Yeah!*
