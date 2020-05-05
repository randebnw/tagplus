<?php

namespace TagplusBnw;

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
	
	private $config;
	private $product_config;
	private $length;
	private $weight;
	
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
		
		$this->length = $registry->get('length');
		$this->weight = $registry->get('weight');
		
		$this->config = new \TagplusBnw\Opencart\Config($registry->get('config'));
		$this->product_config = $this->config->get_default_product_config();
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
			$oc_product = \TagplusBnw\Helper::tgp_product_2_oc_product($tgp_product, $this->product_config, $this->length, $this->weight);
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
		$oc_product = \TagplusBnw\Helper::tgp_product_2_oc_product($tgp_product, $this->product_config, $this->length, $this->weight);
		return $this->oc->import_product($oc_product);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 3 de dez de 2019
	 * @param unknown $order
	 * @param unknown $order_totals
	 * @param unknown $products
	 * @param unknown $customer
	 * @param unknown $config
	 * @throws Exception
	 * @return unknown
	 */
	public function add_order($order, $order_totals, $products, $customer) {
		$tgp_order = \TagplusBnw\Helper::oc_order_2_tgp_order($order, $order_totals, $products, $customer, $this->config);
		
		$tgp_id = $this->tgp->add_order($tgp_order);
		if (!$tgp_id) {
			\TagplusBnw\Util\Log::error('Erro ao chamar funcao tgp->add_order');
			\TagplusBnw\Util\Log::error(print_r($tgp_order, true));
		
			throw new Exception();
		}
		
		return $tgp_id;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 15 de abr de 2020
	 * @param unknown $customer
	 * @param unknown $addresses
	 * @throws Exception
	 */
	public function add_customer($customer, $addresses) {
		$tgp_id = $this->tgp->get_customer_id($customer['cpf'], $customer['cnpj']);
		
		// se nao encontrou, entao insere
		if (!$tgp_id) {
			$tgp_customer = \TagplusBnw\Helper::oc_customer_2_tgp_customer($customer, $addresses, $this->config);
			$tgp_id = $this->tgp->add_customer($tgp_customer);
			if (!$tgp_id) {
				\TagplusBnw\Util\Log::error('Erro ao chamar funcao tgp->add_customer');
				\TagplusBnw\Util\Log::error(print_r($tgp_customer, true));
			
				throw new Exception();
			}	
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
	 * @since 15 de abr de 2020
	 */
	public function get_orders_to_export() {
		return $this->oc->get_orders_to_export();
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 15 de abr de 2020
	 * @param unknown $order_id
	 */
	public function get_order_totals($order_id) {
		return $this->oc->get_order_totals($order_id);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 15 de abr de 2020
	 * @param unknown $order_id
	 */
	public function get_order_products($order_id) {
		return $this->oc->get_order_products($order_id);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 5 de mai de 2020
	 */
	public function get_payment_methods() {
		return $this->tgp->get_payment_methods();
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 31 de out de 2019
	 */
	public function get_users() {
		return $this->tgp->get_users();
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 5 de mai de 2020
	 */
	public function get_contact_types() {
		return $this->tgp->get_contact_types();
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 5 de mai de 2020
	 */
	public function get_register_types() {
		return $this->tgp->get_register_types();
	}
}
?>