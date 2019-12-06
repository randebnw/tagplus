<?php

namespace TagplusBnw\Tagplus;

class Api {
	
	const METHOD_GET = 'GET';
	const ALLOWED_PRODUCT_TYPES = array('N', 'G');
	
	private $registry;
	private $auth;
	
	private static $instance;
	
	private function __construct($registry) {
		$this->registry = $registry;
		$this->auth = Auth::get_instance($registry->get('config'));
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
	public static function get_instance($registry) {
		if (self::$instance == null) {
			self::$instance = new \TagplusBnw\Tagplus\Api($registry);
		}
		
		return self::$instance;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 12 de dez de 2018
	 */
	public function get_payment_conditions() {
		return $this->_do_request(self::METHOD_GET, '/formas_pagamento', array(
			'query' => array('ativo' => 1)
		));
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 31 de out de 2019
	 */
	public function get_order_status_list() {
		return array(
			'A' => 'Em aberto',
			'B' => 'Confirmado',
		);
	}
	
	/**
	 *
	 * @author Rande A. Moreira
	 * @since 6 de dez de 2018
	 */
	public function get_products($page, $per_page) {
		$products = array();
	
		$result = $this->_get_products($page, $per_page);
		return $result;
	}
	
	/**
	 *
	 * @author Rande A. Moreira
	 * @since 6 de dez de 2018
	 * @param unknown $date_changed
	 */
	public function get_products_by_date($date_changed) {
		$products = array();
	
		$result = $this->_get_products_by_date($date_changed);
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
		return $this->_do_request(self::METHOD_GET, '/produtos', array(
			'query' => array('ativo' => 1, 'tipo' => implode(',', self::ALLOWED_PRODUCT_TYPES), 'page' => $page, 'per_page' => $per_page)
		));
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
		return $this->_do_request(self::METHOD_GET, '/produtos', array(
			'query' => array('since' => $since, 'tipo' => implode(',', self::ALLOWED_PRODUCT_TYPES), 'page' => $page, 'per_page' => $per_page),
			'header' => array('X-Data-Filter' => 'data_alteracao')
		));
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 31 de out de 2019
	 */
	public function get_users() {
		return $this->_do_request(self::METHOD_GET, '/usuarios', array('query' => array('ativo' => 1)));
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 31 de out de 2019
	 * @param unknown $method
	 * @param unknown $url
	 * @param array $options
	 */
	private function _do_request($method, $url, $options = array()) {
		try {
			// TODO verificar necessidade do cabecalho "X-Api-Version: 2.0"
			$client = $this->auth->authenticate();
			$result = $client->$method($url, $options);
			
			return $result;
		} catch (\Exception $e) {
			return false;
		}
	}	
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 10 de jan de 2019
	 * @param unknown $customer
	 */
	public function add_customer(&$customer) {
		$this->_init_list_zones();
		
		$model_setting = $this->_load_model('setting/setting');
		$tgp_config = $model_setting->getSetting('tagplus');
		$tgp_customer = $this->api->get_customer_by_document($customer['cpf'] ? $customer['cpf'] : $customer['cnpj']);
		if ($tgp_customer) {
			$model_customer = $this->_load_model('account/customer');
			$oc_customer = $model_customer->getCustomerByEmail($customer['email']);
			
			if ($oc_customer && $oc_customer['tgp_id'] == $tgp_customer['customer']['CODIGO']) {
				// email ja ta cadastrado como cliente da loja, avisa o cara pra fazer login
				throw new Exception('Você já está cadastrado na loja virtual. Use seu email e senha para fazer o login.');
			} else if ($tgp_customer['address']['EMAIL'] != $customer['email']) {
				// email nao ta cadastrado como cliente da loja, avisa que ele precisa informar o mesmo email da tagplus pra prosseguir
				throw new Exception('Você já é um cliente da loja física, mas ainda não tem cadastro na loja virtual. Informe o mesmo email de cadastro da loja física para prosseguir com o cadastro.');
			} else {
				// email nao ta cadastrado como cliente da loja e o email informado é IGUAL ao email do Tagplus, entao apenas retorna o ID do cliente para prosseguir com o cadastro
				$customer['customer_group_id'] = $tgp_customer['customer']['CONCEITO'];
				return $tgp_customer['customer']['CODIGO'];
			}
		} else {
			$customer_data = TagplusHelper::oc_customer_2_tgp_customer($customer, $tgp_config);
			$address_data = TagplusHelper::oc_address_2_tgp_address($customer, $tgp_config, $this->list_zones);
			
			return $this->api->add_customer($customer_data, $address_data);
		}
	}
	
	/**
	 * 
	 * @param unknown $tgp_id
	 * @return string
	 */
	public function get_customer_by_id($tgp_id) {
		// TODO get_customer
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 8 de jan de 2019
	 * @param unknown $order
	 */
	public function create_order($order, $itens) {
		// TODO create order
	}
}
?>