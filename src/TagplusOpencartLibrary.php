<?php

namespace TagplusBnw;

use TagplusBnw\Opencart\Api;
use TagplusBnw\Tagplus\Api;
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
		$this->tgp = TagplusBnw\Tagplus\Api::get_instance($registry);
		$this->oc = TagplusBnw\Opencart\Api::get_instance($registry);
		$this->auth = new TagplusBnw\Tagplus\Auth($registry);
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
		$product = $this->api->get_product_simple($tgp_id);
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
	 * @since 3 de dez de 2019
	 * @param unknown $date
	 */
	public function get_products_by_date($date) {
		$products = array();
		$result = $this->tgp->get_products_by_date($conditions);
		foreach ($result as $item) {
			$products[] = DataclassicHelper::dc_product_2_oc_product($item, $product_config);
		}
		
		return $products;
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
			\TagplusBnw\Util\Log::error('Erro ao chamar funcao api->create_order');
			\TagplusBnw\Util\Log::error(print_r($tgp_order, true));
		
			throw new Exception();
		}
		
		return $tgp_id;
	}
}
?>