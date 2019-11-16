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
		$this->auth = new Auth($this->config);
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
			self::$instance = new Api($registry);
		}
		
		return self::$instance;
	}
	
	public function getError() {
		return $this->error;
	}
	
	public function is_success() {
		return !$this->error;
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
	public function get_products($page) {
		$products = array();
		$product_config = $this->_get_default_product_config();
	
		$result = $this->_get_products($page);
		\TagplusBnw\Util\Log::debug('migrando ' . count($result) . ' produtos');
		foreach ($result as $item) {
			$products[] = TagplusHelper::tgp_product_2_oc_product($item, $product_config);
		}
	
		return $products;
	}
	
	/**
	 *
	 * @author Rande A. Moreira
	 * @since 6 de dez de 2018
	 * @param unknown $date_changed
	 */
	public function get_products_by_date($date_changed) {
		$products = array();
		$product_config = $this->_get_default_product_config();
	
		$result = $this->_get_products_by_date($date_changed);
		foreach ($result as $item) {
			$products[] = TagplusHelper::tgp_product_2_oc_product($item, $product_config);
		}
	
		return $products;
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
			$client = $this->auth->authenticate();
			$result = $client->$method($url, $options);
			
			return $result;
		} catch (Exception $e) {
			return false;
		}
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 6 de dez de 2018
	 * @param unknown $tgp_id
	 * @param unknown $product_id
	 */
	public function simple_update_product($tgp_id, $product_id) {
		$this->model->simple_update_product($product_id);
		
		// define operacao padrao
		$product_operation_code = $this->config->get('tgp_order_operation_code');
		
		// busca operacao especifica do produto
		$operation_sql = "
			SELECT c.operation_code FROM " . DB_PREFIX . "category c
			WHERE c.parent_id = 0 AND c.tgp_id IS NOT NULL AND c.category_id IN (
				SELECT p2c.category_id FROM " . DB_PREFIX . "product_to_category p2c
				WHERE p2c.product_id = " . (int) $product_id . " AND p2c.category_id = c.category_id
			)
			LIMIT 0, 1 ";
		
		$result = $this->db->query($operation_sql);
		if ($result && $result->row && !empty($result->row['operation_code'])) {
			$product_operation_code = $result->row['operation_code'];
		}
		
		$this->_sync_stock_price($tgp_id, $product_id, $product_operation_code);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 6 de dez de 2018
	 * @param unknown $tgp_id
	 */
	public function synchronize_product($tgp_id) {
		$product_config = $this->_get_default_product_config();
		
		$result = $this->api->get_product($tgp_id);
		if ($result) {
			$product = TagplusHelper::tgp_product_2_oc_product($result, $product_config);
			return $this->import_product($product);
		}
		
		return false;
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
		$tgp_config = $model_setting->getSetting('dataclassic');
		$tgp_customer = $this->api->get_customer_by_document($customer['cpf'] ? $customer['cpf'] : $customer['cnpj']);
		if ($tgp_customer) {
			$model_customer = $this->_load_model('account/customer');
			$oc_customer = $model_customer->getCustomerByEmail($customer['email']);
			
			if ($oc_customer && $oc_customer['tgp_id'] == $tgp_customer['customer']['CODIGO']) {
				// email ja ta cadastrado como cliente da loja, avisa o cara pra fazer login
				throw new Exception('Você já está cadastrado na loja virtual. Use seu email e senha para fazer o login.');
			} else if ($tgp_customer['address']['EMAIL'] != $customer['email']) {
				// email nao ta cadastrado como cliente da loja, avisa que ele precisa informar o mesmo email do dataclassic pra prosseguir
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
	public function import_customer_by_id($tgp_id) {
		$this->_init_list_zones();
		
		$model_setting = $this->_load_model('setting/setting');
		$tgp_config = $model_setting->getSetting('dataclassic');
		$tgp_customer = $this->api->get_customer_by_id($tgp_id);
		$oc_customer = TagplusHelper::tgp_customer_2_oc_customer($tgp_customer, $tgp_config, $this->config, $this->list_zones);
				
		$model_customer = $this->_load_model('account/customer');
		$customer_exists = $model_customer->getCustomerByEmail($oc_customer['email']);
		if ($customer_exists) {
			return false;
		}
		
		$customer_id = $model_customer->addCustomer($data);
		return $customer_id;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 8 de jan de 2019
	 * @param unknown $order
	 */
	public function add_order($order, $cart) {
		if (isset($this->session->data['new_dataclassic_orders'])) {
			/**
			 * Se estamos criando um novo pedido, mas ainda existem pedidos anteriores na session,
			 * entao é pq o cliente criou algum pedido que ficou perdido (nao confirmado).
			 * Nesse caso, vamos excluir os pedidos anteriores
			 */
			foreach ($this->session->data['new_dataclassic_orders'] as $company_id => $tgp_order_id) {
				$this->api->delete_order($tgp_order_id);
			}
			
			unset($this->session->data['new_dataclassic_orders']);
		}
		
		$this->init_map_company();
		$totals = $order['totals'];
		$products = $order['products'];
		
		$customer_address = $this->model->get_customer_address($this->customer->getId());
		$customer = array(
			'tgp_id' => $this->customer->getDcId(),
			'customer_group_id' => $this->customer->getCustomerGroupId(),
			'uf' => $customer_address['uf']
		);
		
		$model_setting = $this->_load_model('setting/setting');
		$tgp_config = $model_setting->getSetting('dataclassic');
		
		if ($this->config->get($order['payment_code'] . '_tgp_payment_condition')) {
			$order['payment_condition'] = $this->config->get($order['payment_code'] . '_tgp_payment_condition');
		} else {
			throw new Exception('Condição de pagamento não configurada para o meio de pagamento: ' . $order['payment_code'] . ' - ' . $order['payment_method']);
		}
		
		// calcula soma do valor dos produtos
		$total_products = $this->_get_total_products($products);
		
		$tgp_orders = array();
		if ($cart->need_to_split_order()) {
			$sub_carts = $cart->get_sub_carts(true);
			
			if ($this->config->get('tgp_debug')) {
				TagplusLog::debug('ADD_ORDER > SUB_CART > ' . print_r($sub_carts, true));
			}
			
			foreach ($sub_carts as $company_id => $sub_info) {
				$num_operations = count($sub_info);
				
				if ($this->config->get('tgp_debug')) {
					TagplusLog::debug('ADD_ORDER > ' . $company_id . ' > NUM_OPERATIONS > ' . print_r($num_operations, true));
				}
				
				foreach ($sub_info as $operation_code => $products) {
					$products = $this->_define_products_cost($products, $company_id);
					$total_info = $this->_get_multiple_shipping_and_discount($company_id, $num_operations, $order['shipping_code'], $order['shipping_postcode'], $total_products, $totals, $products);
					
					// atualiza o total do pedido, ja que agora temos apenas uma lista de "sub-produtos" do pedido original
					$order['total'] = $total_info['order_total'];
					$tgp_orders[] = TagplusHelper::oc_order_2_tgp_order(
						$order, $total_info['products'], $this->map_company[$company_id], $operation_code, 
						$total_info['shipping'], $total_info['total_discount'], 
						$customer, $tgp_config
					);
				}
			}
		} else {
			// pega o id da empresa, ja que a compra é toda na mesma empresa 
			$company_id = $cart->get_company_id();
			$products = $this->_define_products_cost($products, $company_id);
			
			$total_info = $this->_get_single_shipping_and_discount($order['total'], $total_products, $totals, $products);
			$tgp_orders[] = TagplusHelper::oc_order_2_tgp_order(
				$order, $total_info['products'], $this->map_company[$company_id], $cart->get_operation_code(),
				$total_info['shipping'], $total_info['total_discount'], 
				$customer, $tgp_config
			);
		}
		
		if ($this->config->get('tgp_debug')) {
			TagplusLog::debug('ADD_ORDER > DC_ORDERS > ' . print_r($tgp_orders, true));
		}
		
		$tgp_ids = array();
		foreach ($tgp_orders as $tgp_order) {
			$tgp_id = $this->api->create_order($tgp_order['order'], $tgp_order['itens']);
			if ($tgp_id) {
				$tgp_ids[$tgp_order['order']['CODEMP']] = $tgp_id;
			} else {
				TagplusLog::error('Erro ao chamar funcao api->create_order');
				TagplusLog::error(print_r($tgp_order, true));
				
				// exclui outros pedidos que possam ter sido criados
				foreach ($tgp_ids as $company_id => $tgp_order_id) {
					$this->api->delete_order($tgp_order_id);
				}
				
				throw new Exception();
			}
		}
		
		// guarda o ID dos pedidos criados
		$this->session->data['new_dataclassic_orders'] = $tgp_ids;
		
		return $tgp_ids;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 8 de jan de 2019
	 * @param unknown $total_discount
	 * @param unknown $order_total
	 * @param unknown $totals
	 * @param unknown $products
	 */
	private function _get_single_shipping_and_discount($order_total, $total_products_no_discount, $totals, $products) {
		// obtem frete e desconto
		$shipping = 0;
		foreach ($totals as $total) {
			if ($total['code'] == 'shipping') {
				$shipping = $total['value'];
			}
		}
	
		$total_discount = $this->_get_total_discount($totals);
		$products = $this->_calculate_product_discount($total_discount, $total_products_no_discount, $products);
		$total_products_with_discount = 0;
		foreach ($products as $p) {
			// calcula o valor de desconto proporcional ao valor do produto
			$total_products_with_discount += ($p['total'] - $p['discount']);
		}
	
		// valida se (produtos + frete) - desconto = total pedido
		$products = $this->_adjust_discount($order_total, $shipping, $total_products_with_discount, $products);
	
		return compact('shipping', 'products', 'total_discount');
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 8 de jan de 2019
	 * @param unknown $totals
	 */
	private function _get_total_discount($totals) {
		$discount = 0;
		foreach ($totals as $total) {
			if ($total['value'] < 0) {
				$discount += abs($total['value']);
			}
		}
		
		return $discount;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 8 de jan de 2019
	 * @param unknown $products
	 */
	private function _get_total_products($products) {
		$products_total = 0;
		foreach ($products as $p) {
			$products_total += $p['total'];
		}
	
		return $products_total;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 8 de jan de 2019
	 * @param unknown $total_discount
	 * @param unknown $products
	 * @return unknown
	 */
	private function _calculate_product_discount($total_discount, $total_products_no_discount, $products) {
		foreach ($products as &$p) {
			// calcula o valor de desconto proporcional ao valor do produto
			$total_percent = $p['total'] / $total_products_no_discount;
			$p['discount'] = round($p['total'] - ($total_discount * $total_percent), 2);
		}
		
		return $products;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 8 de jan de 2019
	 * @param unknown $order_total
	 * @param unknown $shipping_value
	 * @param unknown $products_total
	 * @param unknown $products
	 */
	private function _adjust_discount($order_total, $shipping_value, $products_total_with_discount, $products) {
		$diff = $order_total - ($shipping_value + $products_total_with_discount);
		if ($diff > 0) {
			// a soma de valores enviados ta abaixo do total do pedido, ajusta (diminui desconto do primeiro produto)
			$products[0]['discount'] -= $diff;
		} else if ($diff < 0) {
			// a soma de valores enviados ta acima do total do pedido, ajusta (aumenta desconto do primeiro produto)
			$products[0]['discount'] += $diff;
		}
		
		$products[0]['discount'] = round($products[0]['discount'], 2);
		
		return $products;
	}
}
?>