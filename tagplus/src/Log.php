<?php

namespace TagplusBnw;

class Log {
	
	public static function error($object) {
		error_log('[' . date('Y-m-d H:i:s') . '] ' . print_r($object, true) . "\n", 3, DIR_LOGS . 'dc_error.log');
	}
	
	public static function debug($object) {
		error_log('[' . date('Y-m-d H:i:s') . '] ' . print_r($object, true) . "\n", 3, DIR_LOGS . 'dc_debug.log');
	}
}
?>