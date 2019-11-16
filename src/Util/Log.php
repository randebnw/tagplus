<?php

namespace TagplusBnw\Util;

class Log {
	// TODO melhorar reuso das funcoes de log
	// TODO evitar dependencia do diretorio DIR_LOGS
	public static function error($object) {
		error_log('[' . date('Y-m-d H:i:s') . '] ' . print_r($object, true) . "\n", 3, DIR_LOGS . 'tagplus_error.log');
	}
	
	public static function debug($object) {
		error_log('[' . date('Y-m-d H:i:s') . '] ' . print_r($object, true) . "\n", 3, DIR_LOGS . 'tagplus_debug.log');
	}
}
?>