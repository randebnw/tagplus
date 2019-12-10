<?php

namespace TagplusBnw;

use TagplusBnw\Opencart\Api as OpencartApi;
use TagplusBnw\Tagplus\Api as TagplusApi;
use TagplusBnw\Tagplus\Auth;

class TagplusOpencartLibrary {
	
	private static $instance;
	
	/**
	 *
	 * @var TagplusBnw\Tagplus\Api
	 */
	private $tgp;
	
	/**
	 *
	 * @var TagplusBnw\Opencart\Api
	 */
	private $oc;
	
	/**
	 * 
	 * @var Auth
	 */
	private $auth;
	
	private $product_config;
	
	public static function get_instance($registry) {
		if (self::$instance == null) {
			self::$instance = new TagplusOpencartLibrary($registry);
		}
		
		return self::$instance;
	}
	
	private function __construct($registry) {
		$this->oc = \TagplusBnw\Opencart\Api::get_instance($registry);
		$this->auth = \TagplusBnw\Tagplus\Auth::get_instance($registry->get('config'));
		$this->tgp = \TagplusBnw\Tagplus\Api::get_instance($registry, $this->auth);
		
		$config = new \TagplusBnw\Opencart\Config($registry->get('config'));
		$this->product_config = $config->get_default_product_config();
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 3 de dez de 2019
	 */
	public function oauth() {
		$this->auth->oauth();
	}
	
	/**
	 *
	 * @author Rande A. Moreira
	 * @since 09 de dez de 2019
	 */
	public function get_authorization_url() {
		return $this->auth->get_authorization_url();
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 09 de dez de 2019
	 */
	public function is_authorized() {
		$me = '';
		$api = $this->auth->authenticate();
		$response = $api->get('/me');
		if ($response != null) {
			$me = json_decode($response->getBody());
		}
		
		return $me != ''; 
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 3 de dez de 2019
	 * @param unknown $tgp_id
	 */
	public function synchronize_product($tgp_id) {
		$tgp_product = $this->tgp->get_product($tgp_id);
		if ($tgp_product) {
			$oc_product = \TagplusBnw\Helper::tgp_product_2_oc_product($tgp_product, $this->product_config);
			return $this->oc->synchronize_product($oc_product);
		}
		
		return false;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 3 de dez de 2019
	 * @param unknown $tgp_id
	 * @param unknown $product_id
	 */
	public function simple_update_product($tgp_id, $product_id) {
		$tgp_product = $this->tgp->get_product_simple($tgp_id);
		if ($tgp_product) {
			$oc_product = \TagplusBnw\Helper::tgp_simple_product_2_oc_product($tgp_product, $this->product_config);
			return $this->oc->simple_update_product($oc_product, $tgp_id, $product_id);
		}
		
		return false;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 3 de dez de 2019
	 */
	public function import_payment_conditions() {
		$conditions = $this->tgp->get_payment_conditions();
		return $this->oc->import_payment_conditions($conditions);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 4 de dez de 2019
	 * @param number $page
	 * @return unknown
	 */
	public function get_products($page = 1, $per_page = 200) {
		$num_args = func_num_args();
		if ($num_args > 0) {
			$page = func_get_arg(0);
				
			if ($num_args > 1) {
				$per_page = func_get_arg(1);
			}
		}
		
		$result = $this->tgp->get_products($page, $per_page);
		return $result;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 3 de dez de 2019
	 * @param unknown $date
	 */
	public function get_products_by_date($date, $page = 1, $per_page = 200) {
		$num_args = func_num_args();
		if ($num_args > 0) {
			$date = func_get_arg(0);
			if ($num_args > 1) {
				$page = func_get_arg(1);
			}
			
			if ($num_args > 2) {
				$per_page = func_get_arg(2);
			}
		}
		
		$products = array();
		$result = $this->tgp->get_products_by_date($date, $page, $per_page);
		return $result;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 3 de dez de 2019
	 * @param unknown $data
	 */
	public function import_product($tgp_product) {
		$oc_product = \TagplusBnw\Helper::tgp_product_2_oc_product($tgp_product, $this->product_config);
		return $this->oc->import_product($oc_product);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 3 de dez de 2019
	 * @param unknown $order
	 * @param unknown $cart
	 * @throws Exception
	 */
	public function add_order($order, $cart) {
		$tgp_order = $this->oc->convert_order($order, $cart);
		
		$tgp_id = $this->tgp->create_order($tgp_order['order'], $tgp_order['itens']);
		if (!$tgp_id) {
			\TagplusBnw\Util\Log::error('Erro ao chamar funcao tgp->create_order');
			\TagplusBnw\Util\Log::error(print_r($tgp_order, true));
		
			throw new Exception();
		}
		
		return $tgp_id;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 31 de out de 2019
	 */
	public function get_order_status_list() {
		return $this->tgp->get_order_status_list();
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 31 de out de 2019
	 */
	public function get_users() {
		return $this->tgp->get_users();
	}
}
?>