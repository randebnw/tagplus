<?php

namespace TagplusBnw\Tagplus;

use Tagplus\Client;
use kamermans\OAuth2\Persistence\FileTokenPersistence;

class Auth {
	private $client;
	private $config;
	private $token_persistence;
	
	private static $instance;
	
	const TOKEN_FILE = __DIR__ . '/token.tkn';
	const APP_SCOPE = [
		'read:formas_pagamento', 'read:produtos', 
		'read:pedidos', 'write:pedidos',
		'read:clientes', 'write:clientes',
		'read:usuarios', 'read:tipos_contatos', 'read:tipos_cadastros'
	];
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 30 de out de 2019
	 * @param unknown $config
	 */
	private function __construct($config) {
		$this->config = $config;
		$this->token_persistence = new FileTokenPersistence(self::TOKEN_FILE);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 6 de dez de 2019
	 * @param unknown $config
	 */
	public static function get_instance($config) {
		if (self::$instance == null) {
			self::$instance = new \TagplusBnw\Tagplus\Auth($config);
		}
			
		return self::$instance;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 29 de out de 2019
	 */
	public function oauth() {
		Client::getAccessToken(
			$this->_get_token_config(),
			$this->token_persistence
		);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 29 de out de 2019
	 */
	public function get_authorization_url() {
		$token_config = $this->_get_token_config();
		return Client::getAuthorizationUrl(
		    $token_config['client_id'],
		    $token_config['scope']
		);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 30 de out de 2019
	 * @param string $test
	 * @throws Exception
	 * @return \Tagplus\Client
	 */
	public function authenticate($test = false) {
		$this->do_authentication();
		$num_tries = 1;
		if (!$this->client && !$test) {
			do {
				sleep($num_tries);
				$this->do_authentication();
				$num_tries++;
			} while (!$this->client && $num_tries <= 3);
		}
	
		if (!$this->client) {
			throw new Exception('Não foi possível realizar autenticação');
		}
		
		return $this->client;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 30 de out de 2019
	 */
	private function do_authentication() {
		// se ja tiver autenticado, evita uma nova instancia de Client
		if (!$this->client) {
			$this->client = new Client(
				$this->_get_token_config(),
				[],
				$this->token_persistence
			);
		}
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 30 de out de 2019
	 */
	private function _get_token_config() {
		return [
			'client_id' => $this->config->get('tgp_client_id'),
			'client_secret' => $this->config->get('tgp_client_secret'),
			'scope' => self::APP_SCOPE
		];
	}
}
?>