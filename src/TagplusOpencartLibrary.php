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
	
	public function get_instance($registry) {
		if (self::$instance == null) {
			self::$instance = new TagplusOpencartLibrary($registry);
		}
		
		return self::$instance;
	}
	
	private function __construct($registry) {
		$this->tgp = \TagplusBnw\Tagplus\Api::get_instance($registry);
		$this->oc = \TagplusBnw\Opencart\Api::get_instance($registry);
		$this->auth = \TagplusBnw\Tagplus\Auth::get_instance($registry->get('config'));
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
	 * @since 3 de dez de 2019
	 * @param unknown $tgp_id
	 */
	public function synchronize_product($tgp_id) {
		$product = $this->tgp->get_product($tgp_id);
		return $this->oc->synchronize_product($product);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 3 de dez de 2019
	 * @param unknown $tgp_id
	 * @param unknown $product_id
	 */
	public function simple_update_product($tgp_id, $product_id) {
		$product = $this->tgp->get_product_simple($tgp_id);
		$this->oc->simple_update_product($product, $tgp_id, $product_id);
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
		$products = array();
		// TODO paginacao
		$result = $this->tgp->get_products($page, $per_page);
		return $result;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 3 de dez de 2019
	 * @param unknown $date
	 */
	public function get_products_by_date($date) {
		$products = array();
		$result = $this->tgp->get_products_by_date($date);
		return $result;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 3 de dez de 2019
	 * @param unknown $data
	 */
	public function import_product($data) {
		return $this->oc->import_product($data);
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
}
?>