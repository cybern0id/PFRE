<?php
/*
 * Copyright (C) 2004-2016 Soner Tari
 *
 * This file is part of PFRE.
 *
 * PFRE is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PFRE is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PFRE.  If not, see <http://www.gnu.org/licenses/>.
 */

/** @file
 * Includes, defines, and functions used in the Model.
 */

$ROOT= dirname(dirname(dirname(__FILE__)));
$SRC_ROOT= dirname(dirname(__FILE__));

require_once($SRC_ROOT . '/lib/defs.php');
require_once($SRC_ROOT . '/lib/setup.php');
require_once($SRC_ROOT . '/lib/lib.php');

require_once($MODEL_PATH.'/validate.php');

require_once($MODEL_PATH.'/lib/RuleSet.php');
require_once($MODEL_PATH.'/lib/Rule.php');
require_once($MODEL_PATH.'/lib/Timeout.php');
require_once($MODEL_PATH.'/lib/State.php');
require_once($MODEL_PATH.'/lib/FilterBase.php');
require_once($MODEL_PATH.'/lib/Filter.php');
require_once($MODEL_PATH.'/lib/Antispoof.php');
require_once($MODEL_PATH.'/lib/Anchor.php');
require_once($MODEL_PATH.'/lib/NatBase.php');
require_once($MODEL_PATH.'/lib/NatTo.php');
require_once($MODEL_PATH.'/lib/BinatTo.php');
require_once($MODEL_PATH.'/lib/RdrTo.php');
require_once($MODEL_PATH.'/lib/AfTo.php');
require_once($MODEL_PATH.'/lib/DivertTo.php');
require_once($MODEL_PATH.'/lib/DivertPacket.php');
require_once($MODEL_PATH.'/lib/Route.php');
require_once($MODEL_PATH.'/lib/Macro.php');
require_once($MODEL_PATH.'/lib/Table.php');
require_once($MODEL_PATH.'/lib/Queue.php');
require_once($MODEL_PATH.'/lib/Scrub.php');
require_once($MODEL_PATH.'/lib/Option.php');
require_once($MODEL_PATH.'/lib/Limit.php');
require_once($MODEL_PATH.'/lib/LoadAnchor.php');
require_once($MODEL_PATH.'/lib/Include.php');
require_once($MODEL_PATH.'/lib/Comment.php');
require_once($MODEL_PATH.'/lib/Blank.php');

/**
 * Shell command argument types.
 *
 * @attention PHP is not compiled, otherwise would use bindec()
 * 
 * @warning Do not use bitwise shift operator either, would mean 100+ shifts for constant values!
 */
define('NONE',			1);
define('FILEPATH',		2);
define('NAME',			4);
define('NUM',			8);
define('SHA1STR',		16);
define('BOOL',			32);
define('SAVEFILEPATH',	64);
define('JSON',			128);

$Output= '';
$Error= '';

/**
 * Sets or updates $Output with the given message.
 *
 * Output strings are accumulated in global $Output var and returned to View.
 * 
 * @param string $msg Output message.
 */
function Output($msg)
{
	global $Output;

	if ($Output === '') {
		$Output= $msg;
	}
	else {
		$Output.= "\n".$msg;
	}
}

/**
 * Sets or updates $Error with the given message.
 *
 * Error strings are accumulated in global $Error var and returned to View.
 * 
 * @param string $msg Error message.
 */
function Error($msg)
{
	global $Error;

	if ($Error === '') {
		$Error= $msg;
	}
	else {
		$Error.= "\n".$msg;
	}
}

/**
 * Wrapper for controller error logging via syslog.
 *
 * A global $LOG_LEVEL is set in setup.php.
 *
 * @param int $prio	Log priority checked against $LOG_LEVEL
 * @param string $file Source file the function is in
 * @param string $func Function where the log is taken
 * @param int $line	Line number within the function
 * @param string $msg Log message
 */
function pfrec_syslog($prio, $file, $func, $line, $msg)
{
	global $LOG_LEVEL, $LOG_PRIOS;

	try {
		openlog('pfrec', LOG_PID, LOG_LOCAL0);
		
		if ($prio <= $LOG_LEVEL) {
			$func= $func == '' ? 'NA' : $func;
			$log= "$LOG_PRIOS[$prio] $file: $func ($line): $msg\n";
			if (!syslog($prio, $log)) {
				if (!fwrite(STDERR, $log)) {
					echo $log;
				}
			}
		}
		closelog();
	}
	catch (Exception $e) {
		echo 'Caught exception: ',  $e->getMessage(), "\n";
		echo "pfrec_syslog() failed: $prio, $file, $func, $line, $msg\n";
		// No need to closelog(), it is optional
	}
}

/**
 * Escapes chars.
 *
 * Prevents double escapes by default.
 *
 * preg_quote() double escapes, thus is not suitable. It is not possible to
 * make sure that strings contain no escapes, because this function is used
 * over strings obtained from config files too, which we don't have any control over.
 *
 * Example: $no_double_escapes as FALSE is used in the code to double escape the $ char.
 *
 * @param string $str String to process.
 * @param string $chars Chars to escape.
 * @param bool $no_double_escapes Whether to prevent double escapes.
 * @return string Escaped string.
 */
function Escape($str, $chars, $no_double_escapes= TRUE)
{
	if ($chars !== '') {
		$chars_array= str_split($chars);
		foreach ($chars_array as $char) {
			$esc_char= preg_quote($char, '/');
			if ($no_double_escapes) {
				/// First remove existing escapes
				$str= preg_replace("/\\\\$esc_char/", $char, $str);
			}
			$str= preg_replace("/$esc_char/", "\\\\$char", $str);
		}
	}
 	return $str;
}
?>
