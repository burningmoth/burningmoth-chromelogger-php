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
