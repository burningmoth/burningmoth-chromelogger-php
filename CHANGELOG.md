# CHANGELOG

= 2018 Nov 03 / Version 2.3.1 =
* Fixed: removed auto-table-typing, it's annoying.

= 2018 Nov 01 / Version 2.3 =
* Updated: console() and report() functions now support 'group', 'groupCollapsed', 'groupEnd', 'assert' and 'table' $type; $message that is an array of arrays or objects automatically displays as 'table' type.
* Moved: root console() declaration moved to console.php w/function_exists() pre-check and loaded on init().

= 2018 Aug 03 / Version 2.2 =
* Updated: json_prepare() now adds object class name to `___class_name` property.
* Fixed: Closure callable objects failed to serialize for log keys, threw json_prepare() into infinite loop.
* Updated: report_deferred() adds data-chromelogger-version and data-chromelogger-columns attributes <script[data-chromelogger-rows]> nodes.

= 2018 May 20 / Version 2.1.2 =
* Fixed: array_map_flatten_backtrace() didn't always supply trace 'args' value along with 'function'.

= 2018 Mar 30 / Version 2.1.1 =
* Fixed: Object reference id returned by json_prepare() was lacking object #number.

= 2018 Mar 26 / Version 2.1 =
* Updated: console() now logs as 'log' instead of 'info'.
* Updated: json_prepare() adds object classname to enumerated properties rather than nesting it in a parent object.
* Updated: report_deferred() now outputs rows scripting specifically formatted to be parsed by the Firefox ChromeLogger extension.
* Regression: `__proto__` and `length` object and array properties no longer removed. Problematic.
* Removed: `is_webkit` variable.

= 2018 Mar 18 / Version 2.0 =
* Updated: backtrace() now returns flattened array of entry objects instead of message strings.
* Updated: report() and report_deferred(), moved file:line from messages back to chromelogger protocol trace index.

= 2018 Jan 19 / Version 1.7.1 =
* Updated: Replaced PHP 7.2 deprecated while(each()) with foreach() loops.

= 2017 Dec 23 / Version 1.7 =
* Added: chromelogger.js to be included with random namespace when report_deferred() is invoked.
* Added: Removal of __proto__ and length properties from objects and arrays for deferred messages.
* Removed: Check for console object before processing deferred messages. Should be consistently using supporting browsers by now.

= 2017 Dec 03 / Version 1.6.1 =
* Fixed: namespace wasn't updated in global console() function.

= 2017 Dec 03 / Version 1.6 =
* Added: json_prepare() to recursively prepare JSON w/object names intact and account for back references, replaces print_r().
* Added: error_level variable, passed to set_error_handler() $types parameter.
* Deprecated: is_webkit variable. No longer necessary since Firefox upgraded to WebExtensions.
* Added: console.callstack() function in report_deferred() output javascript.
* Updated: BMC\ChromeLogger to BurningMoth\ChromeLogger namespace.
* Updated: Moved changelog record to CHANGELOG.md

= 2017 Oct 19 / Version 1.5 =
* Added: callstack variable, tick() callback, callstack() processessing.

= 2017 Aug 29 / Version 1.4.2 =
* Updated: default max_header_size from 128k to 8k (Apache server default)

= 2017 Aug 10 / Version 1.4.1 =
* Fixed: report() uses $log length for $log_key if passed $trace array is empty.

= 2017 May 15 / Version 1.4 =
* Removed chromelogger.ini settings functionality, replaced with optional variables array passed to init() function.
* Added stack_limit, remove_namespaces, shorten_filepaths, use_console variables.
* Added unnamespace() function to remove namespaces if remove_namespaces is true.
* Updated stack formatting.
* Updated report_deferred() to report any final errors on shutdown.
* Moved console() from global namespace.

= 2016 Oct 20 / Version 1.3 =
* Updated output to display the same for all capable browsers to keep up with a Firefox change.

= 2016 Jun 08 / Version 1.2 =
* Now defers messages if headers have been sent OR if headers size is greater than max_header_size.
* Now checks memory usage, stops logging errors when exceeded.
* Added max_header_size, max_memory_usage, log and is_webkit variables.
* Moved javascript output to report_deferred() shutdown function.

= 2016 May 23 / Version 1.1 =
* Implemented default variables w/overrides from chromelogger.ini

= 2016 May 19 / Version 1.0 =
* Initial release.
