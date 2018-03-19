<?php
/**
 * Sets handlers to post PHP errors / exceptions to browser web console via X-ChromeLogger-Data protocol.
 * @package Burning Moth \ ChromeLogger
 * @author Tarraccas Obremski <tarraccas@burningmoth.com>
 * @copyright Burning Moth Creations Inc. 2016-2017
 */

## LIBRARY NAMESPACE ##
namespace BurningMoth\ChromeLogger {

	/**
	 * @var string|float
	 * @since 1.0
	 */
	const VERSION = '2.0';


	/**
	 * Bind the error handlers.
	 * @since 1.0
	 * @since 1.4
	 *	- pass array of variables
	 */
	function init( $variables = array() ) {

		/**
		 * Initialize variables on init.
		 * @since 1.4
		 */
		if ( $variables ) namespace\variable($variables);

		/**
		 * Display only fatal errors to the screen as these cannot be caught by handlers anyway!
		 * @since 1.0
		 */
		ini_set('display_errors', 1);
		namespace\variable('prev_error_level', error_reporting( E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING ));

		/**
		 * Register error and exception handlers that report errors to web console.
		 * @since 1.0
		 */
		namespace\variable('prev_error_handler', set_error_handler( __NAMESPACE__.'\error_handler', namespace\variable('error_level') ));
		namespace\variable('prev_exception_handler', set_exception_handler(__NAMESPACE__.'\exception_handler'));

		/**
		 * Register shutdown function for messages that couldn't make it into headers ...
		 * @since 1.2
		 */
		register_shutdown_function(__NAMESPACE__.'\report_deferred');

		/**
		 * Register tick function to map script calls.
		 * @since 1.5
		 */
		if ( namespace\variable('callstack') ) {

			declare(ticks=1);
			register_tick_function(__NAMESPACE__.'\tick');

		}

	}


	/**
	 * Manage plugin-specific variables.
	 * @since 1.0
	 *
	 * @param string $key
	 * @param string $value
	 * @return mixed
	 */
	function variable( $key = null, $value = null ) {

		// store variables ...
		static $variables;
		if ( !isset($variables) ) {

			// default variables ...
			$variables = array(

				/**
				 * Pass errors / exceptions back to overridden handlers if they exist ?
				 * @since 1.1
				 * @var bool
				 */
				'passback_errors'	=> true,


				/**
				 * Max size (bytes) that the X-ChromeLogger-Data header can be.
				 * Exceeding this size will begin to defer messages.
				 * HTTP does not define a limit; however, servers do.
				 *	- Apache	8k
				 *	- IIS 		8k - 16k
				 *	- nginx		4k - 8k
				 *	- Tomcat	8k - 48k
				 *
				 * @since 1.2
				 *
				 * @var int Number of bytes ( default 8K )
				 */
				'max_header_size'	=> 8128,


				/**
				 * Memory usage limit before we stop logging errors.
				 *
				 * @since 1.2
				 *
				 * @var int Number of bytes ( default 50% of php memory limit )
				 */
				'max_memory_usage'	=> namespace\memory_limit()/2,


				/**
				 * Distinction for webkit browsers ...
				 * @since 1.2
				 * @deprecated 1.5
				 * @remove 2.0
				 * @var bool
				 */
				'is_webkit'			=> preg_match('/webkit/i', $_SERVER['HTTP_USER_AGENT']),


				/**
				 * Limit the number of entries in the backtrace stack.
				 * @since 1.4
				 * @var int
				 */
				'stack_limit'		=> 0,


				/**
				 * Remove namespaces from functions and classes in backtrace.
				 * @since 1.4
				 * @var bool
				 */
				'remove_namespaces'	=> true,


				/**
				 * Shorten file paths.
				 * @since 1.4
				 * @var bool
				 */
				'shorten_filepaths'	=> isset($_SERVER['DOCUMENT_ROOT']),


				/**
				 * Use global level console() function (if it doesn't already exist).
				 * @since 1.4
				 * @var bool
				 */
				'use_console'		=> !function_exists('console'),


				/**
				 * Render call stack / WARNING: this is HUGE and BRUTAL!!!
				 * @since 1.5
				 * @var bool
				 */
				'callstack'			=> false,


				/**
				 * Error levels to report.
				 * @since 1.6
				 * @var bitwise int
				 */
				'error_level'=> E_ALL,

			);

		}

		// no key ? return variables array ...
		if ( is_null($key) ) {
			return $variables;
		}

		// set variables (and return) from array - replaces chromelogger.ini nonsense ...
		if ( is_array($key) ) {
			return ( $variables = array_replace($variables, $key) );
		}

		// return value or false if none exists ...
		elseif ( is_null($value) ) {
			return ( array_key_exists($key, $variables) ? $variables[ $key ] : false );
		}

		// set and return value ...
		return ( $variables[ $key ] = $value );
	}


	/**
	 * Adds logged call branches to a trunk in descending order.
	 *
	 * @since 1.5
	 *
	 * @param array $stack
	 *	- reference to parent stack item ...
	 * @param array $trace
	 *	- backtrace array ...
	 */
	function callstack( &$stack, $trace ) {

		// no trace ? stop ...
		if ( empty($trace) ) return;

		// detach first trace item ...
		$item = array_shift($trace);

		// key to record this stack entry under ...
		$key = '';

		// process file:line ...
		if ( isset($item['file']) ) {
			$key = $item['file'] = namespace\shortfile($item['file']) . ':' . $item['line'];
		} else $item['file'] = '';

		// get class and/or function ...
		if ( isset($item['function']) ) {
			$key = $item['function'] = (
				isset($item['class'])
				? namespace\unnamespace($item['class']) . $item['type'] . $item['function']
				: namespace\unnamespace($item['function'])
			);
		} else $item['function'] = '';

		// stack entry doesn't exist ? create it ...
		if ( !array_key_exists($key, $stack) ) {

			$stack[ $key ] = array(
				'caller' => array()
			);

			if ( $item['file'] ) $stack[ $key ]['file'] = $item['file'];

		}

		// add arguments ...
		if ( !empty($item['args']) ) {

			if ( !isset($stack[ $key ]['arg']) ) $stack[ $key ]['arg'] = array();

			$item['args'] = implode(', ', array_map(__NAMESPACE__.'\array_map_flatten_backtrace_args', $item['args']));

			if ( !in_array( $item['args'], $stack[ $key ]['arg'] ) ) {

				$stack[ $key ]['arg'][] = $item['args'];

			}

		}

		// call stack on next trace entry ...
		if ( $trace ) namespace\callstack( $stack[ $key ]['caller'], $trace );

	}


	/**
	 * register_tick_function() callback.
	 * Constructs a call hierarchy from backtrace.
	 *
	 * @since 1.5
	 */
	function tick() {

		// array of callbacks ...
		static $stack;
		if ( !isset($stack) ) $stack = array();

		// process callbacks from backtrace ...
		namespace\callstack( $stack, array_slice(debug_backtrace(), 1) );

		// add to log / always deferred !
		$log =& namespace\log();
		if ( array_key_exists('callstack', $log) ) $log['callstack']['message'] = $stack;
		else {
			$log[ 'callmap' ] = array(
				'message' => $stack,
				'trace' => array(),
				'type' => 'callstack',
				'label' => 'Call Stack',
				'num' => 1,
				'deferred' => true
			);
		}

	}



	/**
	 * Messages log.
	 * Return reference. ex. $log =& namespace\log();
	 *
	 * @since 1.2
	 *
	 * return array
	 */
	function &log() {
		static $log = array();
		return $log;
	}


	/**
	 * Report messages to web console.
	 * @since 1.0
	 *
	 * @param mixed $message
	 *	- Message or data to print.
	 *
	 * @param array $trace
	 *	- Array returned from backtrace() function.
	 *
	 * @param string|integer $type
	 *	- Error type, constant or number.
	 *
	 * @return void
	 */
	function report( $message, $trace = array(), $type = 'info' ) {

		// message log ...
		$log =& namespace\log();

		// exceeding memory limits ? no more logging ...
		if (
			( $max_memory_usage = namespace\variable('max_memory_usage') )
			&& memory_get_usage() > $max_memory_usage
		) {

			// ensure passback errors ...
			namespace\variable('passback_errors', true);

			// final message key ...
			$log_key = 'maxed_out';

			// post final message ...
			if ( !array_key_exists($log_key, $log) ) {

				$log[ $log_key ] = array(
					'message'	=> 'Chrome Logger has exceded the memory usage limit and has ceased logging additional messages.',
					'trace'		=> array(),
					'type'		=> 'warn',
					'label'		=> 'Warning:',
					'num'		=> 1,
					'deferred'	=> true
				);

			}

			// exit stage left ...
			return;
		}

		// message key ...
		$log_key = serialize($message);
		if ( $trace ) $log_key .= '@' . end($trace)->trace;
		else $log_key .= '#' . strval(count($log));
		$log_key = md5($log_key);

		// increment, don't repeat log messages ...
		if ( array_key_exists($log_key, $log) ) {

			$log_msg = $log[ $log_key ];
			$log_msg['num']++;
			extract($log[ $log_key ], EXTR_SKIP);

		}

		else {

			// determine label ...
			$label = '';

			$labels = array(
				E_USER_NOTICE		=> 'Notice',
				E_NOTICE			=> 'Notice',
				E_DEPRECATED		=> 'Deprecated',
				E_USER_DEPRECATED	=> 'Deprecated',
				E_USER_WARNING		=> 'Warning',
				E_WARNING			=> 'Warning',
				E_STRICT			=> 'Strict',
				'console'			=> 'Console',
				'db'				=> 'Database',
			);

			if ( array_key_exists($type, $labels) ) {
				$label = $labels[ $type ];
			}

			switch ($type) {
				case 'i':
				case 'info':
				case 'console':
				case E_USER_NOTICE:
					$type = 'info';
					break;

				case 'l':
				case 'log':
				case 'notice':
				case E_NOTICE:
					$type = 'log';
					break;

				case 'w':
				case 'warn':
				case 'warning':
				case E_DEPRECATED:
				case E_USER_DEPRECATED:
				case E_USER_WARNING:
				case E_WARNING:
				case E_STRICT:
					$type = 'warn';
					break;

				case 'e':
				case 'error':
				default:
					$type = 'error';
					break;

			}

			// no label ? set from type ...
			if ( !$label ) $label = ucfirst($type);

			// convert message to inline string ...
			$message = namespace\json_prepare($message);

			// number of times this message has been reported ...
			$num = 1;

			// begin log message ...
			$log_msg = compact('message', 'trace', 'type', 'label', 'num');

			// whether or not to defer the message ...
			static $defer;
			if ( !isset($defer) ) {
				$defer = false;
			}

			// not deferring ? process headers ...
			if ( !$defer ) {

				// headers have been sent, begin deferring ...
				if ( headers_sent() ) {
					$defer = true;
				}

				/**
				 * Headers not sent yet, use chrome logger protocol ...
				 * @see https://craig.is/writing/chrome-logger/techspecs
				 */
				else {

					static $json;
					if ( !isset($json) ) {
						$json = array(
							'version'	=> namespace\VERSION,
							'columns'	=> array('log', 'backtrace', 'type'),
							'rows'		=> array()
						);
					}

					// reverse the trace so the problem entry is at the beginning ...
					$trace = array_reverse($trace);

					// construct main message ...
					$row = array(
						array(
							$label . ( $num > 1 ? '['.$num.']' : '' ) . ':',
							$message
						)
					);
					if ( $type != 'log' ) array_push($row, null, $type);

					// report the error message ...
					$json['rows'][] = $row;

					// process stack trace ...
					if ( $entry = reset($trace) ) {

						// start group with first trace entry ...
						$json['rows'][] = array(
							array( 'Trace:', $entry->message ),
							'@ ' . $entry->trace,
							'groupCollapsed'
						);

						// file subsequent trace entries in the group ...
						while( $entry = next($trace) ) {

							$row = array(
								array( '«', $entry->message ),
							);
							if ( $entry->trace ) $row[] = '@ ' . $entry->trace;

							$json['rows'][] = $row;

						}

						// end the group ...
						$json['rows'][] = array(array(), null, 'groupEnd');

					}

					// set the header if header size is within stated limits ...
					if (
						( $header = json_encode($json) )
						&& ( $header = base64_encode(utf8_encode($header)) )
						&& (
							1 > namespace\variable('max_header_size')
							|| strlen($header) <= namespace\variable('max_header_size')
						)
					) header( 'X-ChromeLogger-Data: ' . $header );

					// too big or something wrong ! defer messages from now on ...
					else $defer = true;

				}

				// ensure passback errors ...
				if ( $defer ) namespace\variable('passback_errors', true);

			}

			// msg deferred ?
			$log_msg['deferred'] = $defer;

		}

		// save log ...
		$log[ $log_key ] = $log_msg;

	}


	/**
	 * Registered shutdown function. Prints out deferred messages that didn't make it into the headers.
	 *
	 * @since 1.2
	 * @since 1.4
	 *	- report last error ...
	 */
	function report_deferred() {

		$log =& namespace\log();

		/**
		 * Report any last error ...
		 */
		if ( $error = error_get_last() ) {
			namespace\report(
				strip_tags($error['message']),
				array( (object) array(
					'message' => '',
					'trace' => namespace\shortfile($error['file']) . ':' . $error['line']
				) ),
				$error['type']
			);
		}

		/**
		 * Headers are sent and not the ajax script so print to document body ...
		 */
		if (
			// headers have been sent ...
			headers_sent()

			// is an html document ...
			&& preg_grep('#content-type: text/html#i', headers_list())

			// has deferred log messages to post ...
			&& ( $log = array_filter($log, function($msg){ return $msg['deferred']; }) )
		) {

			print '<script type="text/javascript">/* <![CDATA[ */ ';

			// load namespaced functions ...
			$ns = '_' . md5( time() );
			print '(function( ns ){ window[ ns ] = ';
			include __DIR__ . '/chromelogger.js';
			printf(' })("%s"); ', $ns);

			while ( $log ) {

				// get message ...
				$msg = array_shift($log);
				extract($msg);

				// ensure type ...
				if ( empty($type) ) $type = 'log';

				// process label ...
				if ( $num > 1 ) $label .= '[' . $num . ']';
				$label .= ':';

				// output to web console ...
				printf(
					'console.%s(%s, %s.cleanObjectProperties(%s)); ',
					$type,
					json_encode( $label ),
					$ns,
					json_encode( $message )
				);

				// read stack trace in reverse ...
				if ( $entry = end($trace) ) {

					// compose row ...
					$row = array(
						'Trace:',
						$entry->message
					);
					if ( $entry->trace ) $row[] = $entry->trace;

					// print console ...
					printf(
						'console.groupCollapsed(%s); ',
						trim(json_encode($row),'[]')
					);

					// loop through remaining stack trace ...
					while ( $entry = prev($trace) ) {

						$row = array( '«', $entry->message );
						if ( $entry->trace ) array_push($row, '@', $entry->trace);

						printf(
							'console.log(%s); ',
							trim(json_encode($row),'[]')
						);

					}

					print 'console.groupEnd(); ';

				}



			}

			print('/* ]]> */</script>');

		}

	}


	/**
	 * Ensure newer error constants exist.
	 * @since 1.0
	 */
	if ( !defined('E_STRICT') ) define('E_STRICT', 2048);
	if ( !defined('E_RECOVERABLE_ERROR') ) define('E_RECOVERABLE_ERROR', 4096);
	if ( !defined('E_DEPRECATED') ) define('E_DEPRECATED', 8192);
	if ( !defined('E_USER_DEPRECATED') ) define('E_USER_DEPRECATED', 16384);


	/**
	 * Error handler.
	 * @since 1.0
	 */
	function error_handler( $code, $message, $file, $line, $context ) {

		// error is being suppressed via @ operator, do not continue with normal error handling ...
		if ( error_reporting() === 0 ) return;

		// report error to web console ...
		namespace\report( strip_tags($message), namespace\backtrace($file, $line, 2), $code );

		// pass through to previous error handler ...
		if (
			namespace\variable('passback_errors')
			&& ( $func = namespace\variable('prev_error_handler') )
		) return call_user_func_array($func, func_get_args());

		// continue with normal error handling ...
		return false;
	}


	/**
	 * Exception handler.
	 * @since 1.0
	 */
	function exception_handler( $E ) {

		// report error to web console ...
		namespace\report($E->getMessage(), namespace\backtrace($E->getFile(), $E->getLine(), 2), $E->getCode());

		// pass through to previous exception handler ...
		if (
			namespace\variable('passback_errors')
			&& ( $func = namespace\variable('prev_exception_handler') )
		) call_user_func($func, $E);

	}


	/**
	 * Return a flattened backtrace array.
	 * @since 1.0
	 *
	 * @param string $file
	 *	- Filepath the trace is being called from.
	 *
	 * @param integer $line
	 *	- File line the trace is being called from.
	 *
	 * @param integer $n
	 *	- Number of levels off the end to remove so that this function isn't represented.
	 *
	 * @return array
	 */
	function backtrace( $file = null, $line = null, $n = 1 ) {

		// get trace stack ...
		$trace = debug_backtrace();

		// level off this and any other reporting functions from the stack ...
		$trace = array_slice($trace, $n);

		// add file:line entry where error occurred if not already represented in stack trace ...
		if (
			$file && $line
			&& (
				(
					( $entry = current($trace) )
					&& ( $entry['file'] != $file )
					&& ( $entry['line'] != $line )
				)
				|| !$entry
			)
		) array_unshift($trace, compact('file', 'line'));

		// limit stack trace ...
		if ( $limit = namespace\variable('stack_limit') ) $trace = array_slice($trace, 0, $limit);

		// reverse the so most recent trace is at the top nearest the report ...
		$trace = array_reverse($trace);

		// flatten backtrace ...
		$trace = array_map(__NAMESPACE__.'\array_map_flatten_backtrace', $trace);

		return $trace;

	}


	/**
	 * array_map() callback to flatten results from debug_backtrace() through.
	 * @since 1.0
	 * @since 2.0
	 *	- return value is object instead of string
	 * @return object
	 */
	function array_map_flatten_backtrace( $trace ) {

		$entry = (object) [
			'message' => '',
			'trace' => null,
		];

		// included path ...
		if (
			isset($trace['function'])
			&& in_array($trace['function'], array('require', 'require_once', 'include', 'include_once'))
		) $entry->message = namespace\shortfile( isset($trace['args']) ? current($trace['args']) : $trace['file'] );

		// functions, classes, etc.
		else {

			// prefix with class name ...
			if ( isset($trace['class']) ) {
				$entry->message .= namespace\unnamespace($trace['class']) . $trace['type'];
			}

			// add function name, arguments ...
			if ( isset($trace['function']) ) {
				$entry->message .= namespace\unnamespace($trace['function']) . '(' . implode(', ', array_map(__NAMESPACE__.'\array_map_flatten_backtrace_args', $trace['args'])) . ')';
			}

		}

		// set file:line ...
		if ( isset($trace['file']) ) {
			$entry->trace = namespace\shortfile($trace['file']) . ':' . $trace['line'];
		}

		// return value ...
		return $entry;

	}


	/**
	 * array_map() callback to flatten arguments from an element of debug_backtrace().
	 * @since 1.0
	 */
	function array_map_flatten_backtrace_args( $arg ) {

		if ( is_bool($arg) ) {
			return ( $arg ? 'TRUE' : 'FALSE' );
		}

		elseif ( is_numeric($arg) ) {
			return gettype($arg) . '[' . $arg . ']';
		}

		elseif ( is_callable($arg) ) {

			if ( is_object($arg) ) {
				return 'callable[' . namespace\unnamespace(get_class($arg)) . ']';
			}

			elseif ( is_array($arg) ) {
				return 'callable[' . ( is_object($arg[0]) ? namespace\unnamespace(get_class($arg[0])) : $arg[0] ) . '::' . $arg[1] . ']';
			}

			elseif ( is_string($arg) ) {
				return 'callable[lambda]';
			}

			else {
				return 'callable';
			}

		}

		elseif ( is_object($arg) ) {
			return namespace\unnamespace(get_class($arg));
		}

		elseif ( is_array($arg) ) {
			return 'array[' . count($arg) . ']';
		}

		elseif ( is_string($arg) ) {
			return '"' . ( strlen($arg) > 10 ? substr($arg, 0, 10) . '…' : $arg ) . '"';
		}

		else {
			return gettype($arg);
		}

	}


	/**
	 * Ensure forward slashes in file paths.
	 * @since 1.0
	 *
	 * @param string $str File pathname.
	 * @return string
	 */
	function fwdslashes( $str ) {
		return str_replace('\\', '/', $str);
	}


	/**
	 * Remove the document root path from file paths to shorten them.
	 * @since 1.0
	 *
	 * @param string $path Filepath.
	 * @return string
	 */
	function shortfile( $path ) {
		return ( namespace\variable('shorten_filepaths') ? ltrim(str_replace( namespace\fwdslashes($_SERVER['DOCUMENT_ROOT']), '', namespace\fwdslashes($path) ), '/') : namespace\fwdslashes($path) );
	}


	/**
	 * Remove namespace from class and function names.
	 * @since 1.4
	 * @param string $name
	 * @return string
	 */
	function unnamespace( $name ) {
		return ( namespace\variable('remove_namespaces') ? basename($name) : $name );
	}


	/**
	 * Return the PHP memory limit.
	 * @since 1.2
	 * @return int bytes
	 */
	function memory_limit() {

		$bytes = ini_get('memory_limit');

		// integer (ex.
		if (
			( is_integer($bytes) || is_numeric($bytes) )
			&& $bytes > 0
		) {
			return intval($bytes);
		}

		// string (ex. '256M')
		elseif ( preg_match('/^(\d*)M$/', $bytes, $match) ) {
			return intval(end($match)) * 1048576;
		}

		// default 128M ...
		return 128 * 1048576;

	}


	/**
	 * Renders objects descriptive w/classname and accounts for back references for JSON.
	 * @since 1.6
	 * @param mixed $var
	 * @return mixed
	 */
	function json_prepare( $var ) {

		// references ...
		static $refs;
		if ( !isset($refs) ) $refs = [];

		// process objects ...
		if ( is_object( $var ) ) {

			// get classname ...
			$classname = namespace\unnamespace( get_class( $var ) );

			// get reference ...
			ob_start();
			debug_zval_dump($var);
			if ( preg_match('/#\d+/', ob_get_clean(), $matches) ) {

				// ammend classname to create reference ...
				$classname .= current($matches);

				// object already referenced ? return reference name ...
				if ( in_array($classname, $refs) ) return $classname;

				// add to references ...
				else $refs[] = $classname;

			}

			// enumerate properties ...
			$properties = array_map(__NAMESPACE__.'\json_prepare', (array) $var);

			// return a descriptive object array for json ...
			return [ $classname => $properties ];

		}

		// process arrays ...
		elseif ( is_array( $var ) ) {
			$var = array_map(__NAMESPACE__.'\json_prepare', $var);
		}

		// pass through everything else ...
		return $var;

	}


	/**
	 * Manually pass information to the web console.
	 * @since 1.0
	 * @since 1.4
	 *	- copied this function from global namespace in case global console() is not available.
	 *
	 * @param mixed $message Message or data to display in the console.
	 * @param string|integer $type Error type, constant or number.
	 * @param string $file Path of the file calling from.
	 * @param integer $line Line of the file calling from.
	 * @return void
	 */
	function console( $message, $type = 'console', $file = null, $line = null ) {
		namespace\report($message, namespace\backtrace($file, $line, 1), $type);
	}

}

### GLOBAL NAMESPACE ###
namespace {

	if ( BurningMoth\ChromeLogger\variable('use_console') ) {

		function console( $message, $type = 'console', $file = null, $line = null ) {
			BurningMoth\ChromeLogger\report($message, BurningMoth\ChromeLogger\backtrace($file, $line, 1), $type);
		}

	}


}