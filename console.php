<?php
/**
 * Declare console() function at root level.
 * @since 2.3
 */

// console() already declared ? error ...
if ( function_exists('console') ) trigger_error('console() is already declared! Use \BurningMoth\ChromeLogger\console() instead!', E_USER_WARNING);

// declare console()
else {

	function console( $message, $type = 'console', $file = null, $line = null ) {
		BurningMoth\ChromeLogger\report($message, BurningMoth\ChromeLogger\backtrace($file, $line, 1), $type);
	}

}
