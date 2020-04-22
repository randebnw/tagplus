<?php

namespace TagplusBnw\Tagplus;

class Api {
	
	const METHOD_GET = 'GET';
	const ALLOWED_PRODUCT_TYPES = ['N', 'G'];
	
	const PRODUCT_FIELDS = [
		'id', 'tipo', 'descricao', 'ativo', 'codigo', 'codigo_barras',
		'valor_venda_varejo', 'qtd_revenda', 
		'comprimento', 'altura', 'largura', 'peso',
		'categoria', 'fornecedores', 'atributos', 'filhos'
	];
	
	private $registry;
	private $auth;
	
	private static $instance;
	
	private function __construct($registry, $auth) {
		$this->registry = $registry;
		$this->auth = $auth;
	}
	
	public function __get($key) {
		return $this->registry->get($key);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 8 de jan de 2019
	 * @param unknown $registry
	 */
	public static function get_instance($registry, $auth) {
		if (self::$instance == null) {
			self::$instance = new \TagplusBnw\Tagplus\Api($registry, $auth);
		}
		
		return self::$instance;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 12 de dez de 2018
	 */
	public function get_payment_conditions() {
		return $this->_do_request(self::METHOD_GET, '/formas_pagamento', [
			'query' => ['ativo' => 1]
		]);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 31 de out de 2019
	 */
	public function get_order_status_list() {
		return [
			'A' => 'Em aberto',
			'B' => 'Confirmado',
		];
	}
	
	/**
	 *
	 * @author Rande A. Moreira
	 * @since 6 de dez de 2018
	 */
	public function get_products($page, $per_page) {
		$result = $this->_get_products($page, $per_page);
		return $result;
	}
	
	/**
	 *
	 * @author Rande A. Moreira
	 * @since 6 de dez de 2018
	 * @param unknown $date_changed
	 */
	public function get_products_by_date($date_changed, $page, $per_page) {
		$result = $this->_get_products_by_date($date_changed, $page, $per_page);
		return $result;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 31 de out de 2019
	 * @param number $page
	 * @param number $per_page
	 */
	private function _get_products($page = 1, $per_page = 200) {
		return $this->_do_request(self::METHOD_GET, '/produtos', [
			'query' => [
				'ativo' => 1, 
				'tipo' => implode(',', self::ALLOWED_PRODUCT_TYPES), 
				'page' => $page, 'per_page' => $per_page,
				'fields' => implode(',', self::PRODUCT_FIELDS)
			]
		]);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 31 de out de 2019
	 * @param unknown $since
	 * @param number $page
	 * @param number $per_page
	 */
	private function _get_products_by_date($since, $page = 1, $per_page = 200) {
		return $this->_do_request(self::METHOD_GET, '/produtos', [
			'query' => [
				'since' => $since, 
				'tipo' => implode(',', self::ALLOWED_PRODUCT_TYPES), 
				'page' => $page, 'per_page' => $per_page,
				'fields' => implode(',', self::PRODUCT_FIELDS)
			],
			'header' => ['X-Data-Filter' => 'data_alteracao']
		]);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 31 de out de 2019
	 */
	public function get_users() {
		return $this->_do_request(self::METHOD_GET, '/usuarios', ['query' => ['ativo' => 1]]);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 15 de abr de 2020
	 * @param unknown $cpf
	 * @param unknown $cnpj
	 * @return boolean|mixed
	 */
	public function get_customer_id($cpf, $cnpj) {
		$cpf = trim($cpf);
		$cnpj = trim($cnpj);
		$filter = $cpf ? ['cpf' => $cpf] : ['cnpj' => $cnpj];
		$customers = $this->_do_request(self::METHOD_GET, '/usuarios', ['query' => $filter]);
		if ($customers !== false && is_array($customers) && isset($customers[0]->id)) {
			return $customers[0]->id;
		}
		
		return false;
	}
	
	/**
	 *
	 * @author Rande A. Moreira
	 * @since 8 de jan de 2019
	 * @param unknown $order
	 */
	public function add_order($order) {
		$order = $this->_do_request(self::METHOD_POST, '/pedidos', ['query' => [$order]]);
		return isset($order->id) ? $order->id : false;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 15 de abr de 2020
	 * @param unknown $customer
	 */
	public function add_customer($customer) {
		$customer = $this->_do_request(self::METHOD_POST, '/clientes', ['query' => [$customer]]);
		return isset($customer->id) ? $customer->id : false;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 31 de out de 2019
	 * @param unknown $method
	 * @param unknown $url
	 * @param array $options
	 */
	private function _do_request($method, $url, $options = []) {
		try {
			// TODO verificar necessidade do cabecalho "X-Api-Version: 2.0"
			$client = $this->auth->authenticate();
			$response = $client->$method($url, $options);
			if ($response !== null) {
				$response = json_decode($response->getBody());
			}
			
			return $response !== null ? $response : false;
		} catch (Exception $e) {
			return false;
		}
	}
}
?>