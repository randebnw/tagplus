<?php

namespace TagplusBnw;

use Tagplus\Client;
use kamermans\OAuth2\Persistence\FileTokenPersistence;

class Auth {
	private $api;
	private $config;
	private $token_persistence;
	
	const TOKEN_FILE = __DIR__ . '/token.tkn';
	const APP_SCOPE = ['read:produtos', 'read:pedidos', 'write:pedidos'];
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 30 de out de 2019
	 * @param unknown $config
	 * @param string $debug
	 */
	public function __construct($config, $debug = false) {
		$this->config = $config;
		$this->token_persistence = new FileTokenPersistence(self::TOKEN_FILE);
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
		if (!$this->api && !$test) {
			do {
				sleep($num_tries);
				$this->do_authentication();
			} while (!$this->api && $num_tries <= 3);
		}
	
		if (!$this->api) {
			throw new Exception('Não foi possível realizar autenticação');
		}
		
		return $this->api;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 30 de out de 2019
	 */
	private function do_authentication() {
		$this->api = new Client(
		    $this->_get_token_config(),
		    [],
		    $this->token_persistence
		);
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