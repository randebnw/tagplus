<?php

namespace TagplusBnw;

class Api {
	
	const METHOD_GET = 'GET';
	
	private $cart;
	private $load;
	private $log;
	private $config;
	private $customer;
	private $language;
	private $registry;
	private $response;
	private $error;
	private $url;

	private $api;
	private $model;
	private $list_companies;
	private $list_companies_info;
	private $list_customer_groups;
	private $list_zones;
	private $list_price_zones;
	private $list_country;
	private $list_payment_conditions;
	private $map_categories;
	private $map_category_operation;
	private $map_sub_categories;
	private $map_manufacturer;
	private $map_company;
	private $map_customer_group;
	private $map_product;
	private $map_similar;
	
	private static $instance;
	
	const NET_WEIGTH_TYPE = 'net';
	const GROSS_WEIGTH_TYPE = 'gross';
	
	const TYPE_CATEGORY = 'category';
	const TYPE_GROUP = 'group';
	
	const TYPE_MANUFACTURER = 'manufacturer';
	const TYPE_BRAND = 'brand';
	
	const CODIGO_SERVICO_FRETE = "0006";
	
	const COD_OPERACAO_VENDA = "S";
	const NUM_OPERACAO_VENDA = "C9";
	
	const PAYMENT_CONDITIONS_CONFIG = 'tgp_payment_conditions';
	const ORDER_STATUS_CONFIG = 'tgp_order_status';
	
	const SITUACAO_ATIVO = "A";
	const SITUACAO_INATIVO = "I";
	
	const VALOR_MINIMO_SEGURO_CARGA = 100.00;
	const ACRESCIMO_SEGURO_CARGA = 0.007;
	const ACRESCIMO_VALOR_CORREIOS = 0.15;
	
	private function __construct($registry) {
		$this->db = $registry->get("db");
		$this->load = $registry->get("load");
		$this->log = $registry->get("log");
		$this->config = $registry->get("config");
		$this->customer = $registry->get("customer");
		$this->language = $registry->get("language");
		$this->request = $registry->get("request");
		$this->response = $registry->get("response");
		$this->session = $registry->get("session");
		$this->url = $registry->get("url");
		$this->registry = $registry;
		
		$this->auth = new Auth($this->config);
		
		$this->model = $registry->get('model_module_dataclassic');
		if (!$this->model && (defined('DIR_CATALOG') || defined('DIR_APPLICATION'))) {
			$base_dir = defined('DIR_CATALOG') ? DIR_CATALOG : DIR_APPLICATION;
			
			// carrega model do catalog
			require_once \VQMod::modCheck($base_dir . 'model/module/tagplus.php');
			$this->model = new \ModelModuleTagplus($this->registry);
		}
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
	
	public function setCustomer($customer) {
		$this->customer = $customer;
	}
	
	public function getCustomer() {
		return $this->customer;
	}
	
	public function getError() {
		return $this->error;
	}
	
	public function is_success() {
		return !$this->error;
	}
	
	public function init_maps() {
		if (!$this->map_product || !$this->map_categories || !$this->map_manufacturer) {
			$categories = $this->model->get_categories();
			foreach ($categories as $item) {
				if ($item['tgp_type'] == 'group') {
					$this->map_categories[$item['tgp_id']] = $item['category_id'];
					$this->map_category_operation[$item['tgp_id']] = $item['operation_code'];
				} else {
					$this->map_sub_categories[$item['parent_id']][$item['tgp_id']] = $item['category_id'];
				}
			}
			
			$manufacturers = $this->model->get_manufacturers();
			foreach ($manufacturers as $item) {
				$this->map_manufacturer[$item['tgp_id']] = $item['manufacturer_id'];
			}
			
			$products = $this->model->get_products();
			foreach ($products as $item) {
				$this->map_product[$item['tgp_id']] = $item['product_id'];
			}	
		}
	}
	
	public function init_reverse_maps() {
		$products = $this->model->get_products();
		foreach ($products as $item) {
			$this->map_product[$item['product_id']] = $item['tgp_id'];
		}
	}
	
	public function init_map_company() {
		if (!$this->map_company) {
			$companies = $this->model->get_companies();
			foreach ($companies as $item) {
				$this->map_company[$item['company_id']] = $item;
			}	
		}
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 8 de mai de 2019
	 */
	public function get_map_company_info() {
		$this->init_map_company();
		return $this->map_company;
	}
	
	public function init_map_customer_group() {
		$model_customer_group = $this->_load_model('account/customer_group');
		
		$customer_groups = $model_customer_group->getCustomerGroups();
		foreach ($customer_groups as $item) {
			$this->map_customer_group[$item['customer_group_id']] = true;
		}
	}
	
	private function _init_list_zones() {
		if (!$this->list_zones || !$this->list_price_zones) {
			// estado de destino
			$model_zone = $this->_load_model('localisation/zone');
			$zones = $model_zone->getZonesByCountryId($this->config->get('config_country_id'));
			$tgp_zone_price_map = $this->config->get('tgp_zone_price_map');
			
			if ($zones) {
				foreach ($zones as $item) {
					$this->list_zones[$item['zone_id']] = $item['code'];
					
					// mapeia precos por estado
					$this->list_price_zones[$item['zone_id']] = isset($tgp_zone_price_map[$item['zone_id']]) ? $tgp_zone_price_map[$item['zone_id']] : $item['code'];
				}
			}	
		}
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 28 de jun de 2019
	 * @param unknown $zone_code
	 */
	public function get_zone_id($zone_code) {
		$this->_init_list_zones();
		if ($zone_code && $this->list_zones) {
			foreach ($this->list_zones as $zone_id => $code) {
				if ($code == $zone_code) {
					return $zone_id;
				}
			}
		}
	
		return 0;
	}
	
	public function init_dependencies() {
		if ($this->list_payment_conditions) {
			// listas ja inicializadas
			return;
		}
		
		// condicoes de pagamento
		$payment_conditions = $this->get_payment_conditions();
		if ($payment_conditions) {
			foreach ($payment_conditions as $item) {
				$this->list_payment_conditions[] = $item['payment_id'];
			}
		}
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
	 * @since 31 de out de 2019
	 * @param number $page
	 * @param number $per_page
	 */
	private function _get_products($page = 1, $per_page = 200) {
		return $this->_do_request(self::METHOD_GET, '/produtos', array(
			'query' => array('ativo' => 1, 'page' => $page, 'per_page' => $per_page)
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
			'query' => array('since' => $since, 'page' => $page, 'per_page' => $per_page),
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
	 * @param unknown $item
	 */
	public function import_product($item) {
		$this->error = '';
		
		// importa categoria se ainda nao existir
		if (isset($item['category']['id']) && !isset($this->map_categories[$item['category']['id']])) {
			if ($cat_id = $this->model->insert_category($item['category'])) {
				$this->map_categories[$item['category']['id']] = $cat_id;
			} else {
				$this->error = 'Erro ao importar categoria ' . $item['category']['id'];
				TagplusLog::error($this->error);
				return false;
			}
		}
		
		// importa subcategoria se ainda nao existir
		$parent_id = $this->map_categories[$item['category']['id']];
		if (isset($item['sub_category']['id']) && !isset($this->map_sub_categories[$parent_id][$item['sub_category']['id']])) {
			if ($cat_id = $this->model->insert_category($item['sub_category'], $parent_id)) {
				$this->map_sub_categories[$parent_id][$item['sub_category']['id']] = $cat_id;
			} else {
				$this->error = 'Erro ao importar sub-categoria ' . $item['sub_category']['id'];
				TagplusLog::error($this->error);
				return false;
			}
		}
		
		// importa fabricante se ainda nao existir
		if (isset($item['manufacturer']['id']) && !isset($this->map_manufacturer[$item['manufacturer']['id']])) {
			if ($manufacturer_id = $this->model->insert_manufacturer($item['manufacturer'])) {
				$this->map_manufacturer[$item['manufacturer']['id']] = $manufacturer_id;
			} else {
				$this->error = 'Erro ao importar fabricante ' . $item['manufacturer']['id'];
				TagplusLog::error($this->error);
				return false;
			}
		}
		
		$item['categories'] = array();
		if (isset($item['category']['id'], $this->map_categories[$item['category']['id']])) {
			$item['categories'][] = $this->map_categories[$item['category']['id']];
		}
		
		if (isset($item['sub_category']['id'], $this->map_sub_categories[$parent_id][$item['sub_category']['id']])) {
			$item['categories'][] = $this->map_sub_categories[$parent_id][$item['sub_category']['id']];
		}
		
		$item['manufacturer_id'] = 0;
		if (isset($item['manufacturer']['id'], $this->map_manufacturer[$item['manufacturer']['id']])) {
			$item['manufacturer_id'] = $this->map_manufacturer[$item['manufacturer']['id']];
		}
		
		$product_id = 0;
		if (isset($this->map_product[$item['tgp_id']])) {
			// UPDATE
			$product_id = $this->map_product[$item['tgp_id']];
			$this->model->update_product($product_id, $item);
		} else {
			// INSERT
			$product_id = $this->model->insert_product($item);
			if ($product_id) {
				$this->map_product[$item['tgp_id']] = $product_id;
			}
		}
		
		if ($product_id) {
			$product_operation_code = isset($this->map_category_operation[$item['category']['id']]) ? $this->map_category_operation[$item['category']['id']] : '';
			if (!$product_operation_code) {
				$product_operation_code = $this->config->get('tgp_order_operation_code');
			}
			
			$this->_sync_stock_price($item['tgp_id'], $product_id, $product_operation_code);
		}
		
		return true;
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
	 * @since 6 de dez de 2018
	 */
	public function get_products($page) {
		$products = array();
		$product_config = $this->_get_default_product_config();
	
		$result = $this->_get_products($page);
		\TagplusBnw\Log::debug('migrando ' . count($result) . ' produtos');
		foreach ($result as $item) {
			$products[] = TagplusHelper::tgp_product_2_oc_product($item, $product_config);
		}
	
		return $products;
	}
	
	public function get_max_product_id() {
		return $this->api->get_max_product_id();
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
	 * Retorna o código do estado (sigla) que deverá ser utilizado para calcular o preço de um determinado estado (zone_id)
	 * 
	 * @author Rande A. Moreira
	 * @since 28 de jun de 2019
	 * @param unknown $zone_id
	 */
	public function get_tgp_price_zone_code($zone_id) {
		$this->_init_list_zones();
		return isset($this->list_price_zones[$zone_id]) ? $this->list_price_zones[$zone_id] : '';
	}
	
	/**
	 * Retorna o zone_id que deverá ser utilizado para calcular o preço de um determinado estado (zone_id)
	 * 
	 * @author Rande A. Moreira
	 * @since 28 de jun de 2019
	 * @param unknown $zone_id
	 */
	public function get_tgp_price_zone_id($zone_id) {
		$this->_init_list_zones();
		$tgp_price_zone_code = $this->get_tgp_price_zone_code($zone_id);
		$tgp_price_zone_id = 0;
		if ($tgp_price_zone_code && $this->list_zones) {
			foreach ($this->list_zones as $zone_id => $code) {
				if ($code == $tgp_price_zone_code) {
					$tgp_price_zone_id = $zone_id;
					break;
				}
			}
		}
		
		return $tgp_price_zone_id;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 19 de dez de 2018
	 * @param unknown $tgp_id
	 * @param unknown $company_id
	 * @param unknown $customer_group_id
	 * @param string $payment_method
	 * @param number $shipping_zone_id
	 */
	public function get_product_price($tgp_id, $company_id, $customer_group_id, $operation = '', $payment_method = '', $shipping_zone_id = 0) {
		if (!$payment_method) {
			$payment_method = isset($this->session->data['payment_method']['code']) ? $this->session->data['payment_method']['code'] : '';
			$payment_method = str_replace('-', '_', $payment_method);
		}
		
		$payment_condition = $this->config->get('tgp_default_payment_condition');
		if ($payment_method && $this->config->get($payment_method . '_tgp_payment_condition')) {
			$payment_condition = $this->config->get($payment_method . '_tgp_payment_condition');
		}
		
		$customer_id = $this->customer->getDcId();
		if (!$customer_id && !$shipping_zone_id) {
			if (isset($this->session->data['guest']['shipping']['zone_id'])) {
				$shipping_zone_id = $this->session->data['guest']['shipping']['zone_id'];
			} else {
				$shipping_zone_id = $this->config->get('tgp_default_zone');
			}
		}
		
		// carrega listas
		$this->init_dependencies();
		$company_info = $this->list_companies_info[$company_id];
		
		if (!$operation) {
			$operation = $this->config->get('tgp_order_operation_code');
		}
		
		$zone_or_customer_id = $customer_id ? $customer_id : ($company_info['customer_type'] . ':' . $this->get_tgp_price_zone_code($shipping_zone_id));
		
		// consumo final é sempre de acordo com a empresa
		$is_final_customer = $company_info['is_final_customer'];
		if (!$this->customer->isLogged() || $this->customer->isPF()) {
			// a nao ser q nao esteja logado, nesse caso força consumo final
			$is_final_customer = true;
		}
		
		$product_db = new TagplusProduct($this->api->get_db());
		$price_info = $product_db->get_price($tgp_id, $company_id, $operation, $payment_condition, $zone_or_customer_id, $customer_group_id, $is_final_customer);
		
		return $price_info;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 22 de jan de 2019
	 * @param unknown $tgp_id
	 * @param unknown $company_id
	 * @param unknown $customer_group_id
	 * @param unknown $operation
	 * @param unknown $payment_condition
	 * @param unknown $zone_id
	 */
	public function get_product_price_admin($tgp_id, $company_id, $customer_group_id, $operation, $payment_condition, $zone_id, $is_final_customer) {
		// carrega listas
		$this->init_dependencies();
		$customer_type = $is_final_customer ? 'F' : 'J';
		$zone = $customer_type . ':' . $this->get_tgp_price_zone_code($zone_id);
	
		$product_db = new TagplusProduct($this->api->get_db());
		$price_info = $product_db->get_price($tgp_id, $company_id, $operation, $payment_condition, $zone, $customer_group_id, $is_final_customer);
	
		return $price_info ? $price_info['VALORFINAL'] : '';
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 7 de jan de 2019
	 * @param unknown $tgp_id
	 * @param unknown $company_id
	 */
	public function get_product_stock($tgp_id, $company_id) {
		$product_db = new TagplusProduct($this->api->get_db());
		$stock_info = $product_db->get_stock($tgp_id, array($company_id));
	
		return $stock_info[$company_id];
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 17 de jan de 2019
	 * @param unknown $tgp_id
	 * @param unknown $company_id
	 * @param unknown $quantity
	 */
	public function increase_product_stock($tgp_id, $company_id, $quantity) {
		$this->model->reduce_product_stock($tgp_id, $company_id, $quantity);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 17 de jan de 2019
	 * @param unknown $tgp_id
	 * @param unknown $company_id
	 * @param unknown $quantity
	 */
	public function reduce_product_stock($tgp_id, $company_id, $quantity) {
		$this->model->reduce_product_stock($tgp_id, $company_id, $quantity);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 16 de jan de 2019
	 * @param unknown $order_id
	 * @param unknown $tgp_id
	 * @param unknown $company_id
	 */
	public function insert_dataclassic_order($order_id, $tgp_id, $company_id) {
		$this->model->insert_dataclassic_order($order_id, $tgp_id, $company_id);
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
	 * @since 23 de abr de 2019
	 * @param unknown $tgp_id
	 */
	public function is_final_customer($tgp_id) {
		return $this->api->is_final_customer($tgp_id);
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
	 * @since 8 de fev de 2019
	 * @param unknown $products
	 */
	private function _define_products_cost($products, $company_id) {
		$map_costs = array();
		$product_db = new TagplusProduct($this->api->get_db());
		
		foreach ($products as $key => $item) {
			if (!isset($map_costs[$company_id][$item['tgp_id']])) {
				$cost = $product_db->get_cost($item['tgp_id'], $company_id);
				if (!$cost) {
					throw new Exception('Erro ao recuperar custo do produto ' . $item['tgp_id']);
				} else {
					$map_costs[$company_id][$item['tgp_id']] = $cost;
				}
			} else {
				$cost = $map_costs[$company_id][$item['tgp_id']];
			}
			
			$products[$key]['custo'] = $cost['CUSTO'];
			$products[$key]['custo_compra'] = $cost['CUSTOCOMPRA'];
		}
		
		return $products;
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
	 * @param unknown $company_id
	 * @param unknown $shipping_code
	 * @param unknown $postcode
	 * @param unknown $total_products_no_discount
	 * @param unknown $totals
	 * @param unknown $products
	 * @throws Exception
	 */
	private function _get_multiple_shipping_and_discount($company_id, $num_operations, $shipping_code, $postcode, $total_products_no_discount, $totals, $products) {
		$shipping = 0;
		$cookie_key = $this->model->get_shipping_cookie_key($shipping_code, $postcode, $company_id, $products);
		if (isset($this->request->cookie[$cookie_key])) {
			$shipping = (float) $this->request->cookie[$cookie_key];
			
			// nesse momento temos o frete apenas por empresa, entao vamos dividir aqui o valor do frete pela numero de "operacoes de venda", ja que cada operacao tera um pedido diferente
			$shipping = round($shipping / $num_operations, 2);
		} else {
			TagplusLog::debug('Frete não encontrado para a empresa ' . $company_id . '. CEP: ' . $postcode . ' FRETE: ' . $shipping_code);
			TagplusLog::debug($cookie_key);
			TagplusLog::debug(print_r($this->request->cookie, true));
			TagplusLog::debug(print_r($products, true));
			throw new Exception();
		}
	
		$total_discount = $this->_get_total_discount($totals);
		$products = $this->_calculate_product_discount($total_discount, $total_products_no_discount, $products);
		$total_products_with_discount = 0;
		foreach ($products as $p) {
			// soma o valor dos produtos sem o desconto
			$total_products_with_discount += ($p['total'] - $p['discount']);
		}
		
		// calcula o valor do "sub-pedido"
		$order_total = $total_products_with_discount + $shipping;
		return compact('shipping', 'products', 'order_total', 'total_discount');
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
		
		return $products;
	}
	
	/**
	 *
	 * @author Rande A. Moreira
	 * @since 16 de jun de 2019
	 * @param unknown $tgp_id
	 */
	public function update_order_status($order, $tgp_status) {
		$tgp_ids = explode('#', $order['tgp_ids']);
		return $this->api->update_order_status($tgp_ids, $tgp_status);
	}
	
	public function update_order_status_from_dc($order, $order_status_map, $model, &$emails_sent) {
		$tgp_ids = explode('#', $order['tgp_ids']);
		$tgp_status = $this->api->get_order_status($tgp_ids);
		if ($tgp_status && $tgp_status->rows) {
			$all_status = array();
			foreach ($tgp_status->rows as $item) {
				$all_status[] = $item['status'];
			}
			
			$all_status = array_unique($all_status);
			
			// so altera o status do pedido na loja se o status de todos os pedidos relacionados foram os mesmos
			if (count($all_status) == 1) {
				$final_status = array_shift($all_status);
				if (isset($order_status_map[$final_status])) {
					$new_status = $order_status_map[$final_status];
					if ($new_status && $new_status != $order['order_status_id']) {
						$model->update($order['order_id'], $new_status, 'Atualização automática de status', true);
						$this->model->update_tgp_order_status($order['order_id'], $final_status);
						$emails_sent++;
					}
				}	
			}
		}
		
		// se o status for retornado pela API, mas nao estiver mapeado no sistema, vai passar como "true"
		return $tgp_status;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 11 de dez de 2018
	 */
	public function get_companies() {
		$companies = $this->api->get_companies();
		$oc_companies = array();
		if ($companies) {
			foreach ($companies as $item) {
				$oc_companies[] = TagplusHelper::tgp_company_2_oc_company($item);
			}
		}
		return $oc_companies;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 11 de dez de 2018
	 */
	public function get_customer_groups() {
		$groups = $this->api->get_customer_groups();
		$oc_groups = array();
		if ($groups) {
			foreach ($groups as $item) {
				$oc_groups[] = TagplusHelper::tgp_customer_group_2_oc_customer_group($item);
			}
		}
		return $oc_groups;
	}
	
	/**
	 *
	 * @author Rande A. Moreira
	 * @since 6 de dez de 2018
	 * @param unknown $item
	 */
	public function import_company($item) {
		$this->error = '';

		if (isset($this->map_company[$item['company_id']])) {
			// UPDATE
			$this->model->update_company($item['company_id'], $item);
		} else if ($this->model->insert_company($item)) {
			// INSERT
			$this->map_company[$item['company_id']] = true;
		}
	
		return true;
	}
	
	public function import_customer_group($item) {
		$this->error = '';

		if (isset($this->map_customer_group[$item['customer_group_id']])) {
			// UPDATE
			$this->model->update_customer_group($item['customer_group_id'], $item);
		} else if ($this->model->insert_customer_group($item)) {
			// INSERT
			$this->map_customer_group[$item['customer_group_id']] = true;
		}
	
		return true;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 11 de dez de 2018
	 */
	public function import_payment_conditions() {
		$payment_conditions = $this->api->get_payment_conditions();
		$oc_payment_conditions = array();
		if ($payment_conditions) {
			foreach ($payment_conditions as $item) {
				$oc_payment_conditions[] = TagplusHelper::tgp_payment_condition_2_oc_payment_condition($item);
			}
		}
		
		if ($oc_payment_conditions) {
			$model_setting = $this->_load_model('setting/setting');
			$model_setting->editSettingValue('dataclassic_payment_conditions', self::PAYMENT_CONDITIONS_CONFIG, $oc_payment_conditions);
		}
		
		return $oc_payment_conditions;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 14 de jun de 2019
	 */
	public function import_order_status() {
		$order_statuses = $this->api->get_list_order_status();
		$oc_order_status = array();
		if ($order_statuses) {
			foreach ($order_statuses as $item) {
				$oc_order_status[] = TagplusHelper::tgp_order_status_2_oc_order_status($item);
			}
		}
	
		if ($oc_order_status) {
			$model_setting = $this->_load_model('setting/setting');
			$model_setting->editSettingValue('dataclassic_order_status', self::ORDER_STATUS_CONFIG, $oc_order_status);
		}
	
		return $oc_order_status;
	}
	
	public function get_orders($extra_fields = array()) {
		return $this->model->get_orders($extra_fields);
	}
	
	public function get_orders_status() {
		return $this->model->get_orders_status();
	}
	
	public function get_orders_paid() {
		return $this->model->get_orders_paid();
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 11 de dez de 2018
	 * @param unknown $tgp_id
	 * @param unknown $oc_id
	 */
	private function _sync_stock_price($tgp_id, $oc_id, $operation) {
		$this->init_dependencies();
		
		$product_db = new TagplusProduct($this->api->get_db());
		$default_company = $this->config->get('tgp_default_company');
		
		if (!$operation) {
			$operation = $this->config->get('tgp_order_operation_code');
		}
		
		if ($this->config->get('tgp_debug')) {
			TagplusLog::debug('ATUALIZANDO PRECO PRODUTO ' . $tgp_id . ' > OPERACAO ' . $operation);
		}
		
		$default_payment_condition = $this->config->get('tgp_default_payment_condition');
		
		$stock_info = $product_db->get_stock($tgp_id, $this->list_companies);
		if ($stock_info) {
			$this->model->update_product_stock($tgp_id, $stock_info);
		}
		
		$main_price = 999999999;
		$min_price = 999999999;
		$unique_price_zones = array_unique($this->list_price_zones);
		foreach ($this->list_companies as $company_id) {
			// se nao tem estoque do produto, entao nao precisa atualizar o preco, ja que ele nao sera exibido na loja
			if ($stock_info[$company_id] <= 0) {
				continue;
			}
			
			$company_info = $this->list_companies_info[$company_id];
			$is_default_company = ($company_id == $default_company);
			foreach ($this->list_customer_groups as $customer_group_id) {
				foreach ($unique_price_zones as $zone_code) {
					// busca usando zone_code (sigla do estado)
					
					// preco para consumo final = 'S' (PF)
					$price_info = $product_db->get_price(
						$tgp_id, $company_id, $operation, 
						$default_payment_condition, 'F' . ':' . $zone_code, 
						$customer_group_id, true
					);
					
					// preco para consumo final = 'N' (PJ)
					$price_info_no_final_customer = $product_db->get_price(
						$tgp_id, $company_id, $operation,
						$default_payment_condition, 'J' . ':' . $zone_code,
						$customer_group_id, false
					);
					
					if ($price_info && $price_info_no_final_customer) {
						$price_info['VALORFINAL_N'] = $price_info_no_final_customer['VALORFINAL'];
						
						// define o preco de acordo com a configuracao da empresa
						$item_price = $price_info['VALORFINAL_N'];
						if ($company_info['is_final_customer']) {
							$item_price = $price_info['VALORFINAL'];
						}
						
						if ($is_default_company) {
							$main_price = min($main_price, $item_price);
						} else {
							$min_price = min($min_price, $item_price);
						}
							
						// salva usando zone_id
						$zone_id = $this->get_zone_id($zone_code);
						$this->model->update_product_price(
							$tgp_id, $oc_id, $company_id, $operation,
							$zone_id, $customer_group_id,
							$price_info, $stock_info[$company_id]
						);
					}
				}
			}
		}
		
		// se nao encontrou valor de main_price, usa o min_price
		if ($main_price == 999999999) {
			$main_price = $min_price;
		}
		
		if ($main_price > 0) {
			$this->model->update_product_default_price($tgp_id, $main_price);
		}
	}
	
	/**
	 *
	 * @author Rande A. Moreira
	 * @since 6 de dez de 2018
	 */
	private function _get_default_product_config() {
		$config['stock_status_id'] = $this->config->get('config_stock_status_id');
		$config['subtract'] = $this->config->get('config_stock_subtract');
		$config['shipping'] = $this->config->get('config_shipping_required');
		$config['weight_class_id'] = $this->config->get('tgp_weight_class');
		$config['length_class_id'] = $this->config->get('tgp_length_class');
		$config['weight_field'] = $this->_get_weight_field();
		$config['category_fields'] = $this->_get_category_fields();
		$config['manufacturer_field'] = $this->_get_manufacturer_field();
	
		return $config;
	}
	
	/**
	 *
	 * @author Rande A. Moreira
	 * @since 6 de dez de 2018
	 */
	private function _get_weight_field() {
		$field = '';
		$config = $this->config->get('tgp_weight_import');
		if ($config == self::NET_WEIGTH_TYPE) {
			$field = 'PESO_LIQ';
		} else if ($config == self::GROSS_WEIGTH_TYPE) {
			$field = 'PESO_BRUTO';
		}
	
		return $field;
	}
	
	/**
	 *
	 * @author Rande A. Moreira
	 * @since 6 de dez de 2018
	 */
	private function _get_category_fields() {
		$field = array('cat' => 'CATEGORIA', 'subCat' => 'SUBCATEGORIA');
		return $field;
	}
	
	/**
	 *
	 * @author Rande A. Moreira
	 * @since 6 de dez de 2018
	 */
	private function _get_manufacturer_field() {
		$field = '';
		$config = $this->config->get('tgp_manufacturer_type');
		if ($config == self::TYPE_MANUFACTURER) {
			$field = 'FABRICANTE';
		} else if ($config == self::TYPE_BRAND) {
			$field = 'MARCA';
		}
	
		return $field;
	}
	
	/**
	 * Essa funcao sempre carrega o model do catalog
	 *
	 * @author Rande A. Moreira
	 * @since 17 de dez de 2018
	 */
	private function _load_model($model_route) {
		$base_dir = defined('DIR_CATALOG') ? DIR_CATALOG : DIR_APPLICATION;
			
		// carrega model do catalog
		require_once VQMod::modCheck($base_dir . 'model/' . $model_route . '.php');
		$class = 'Model' . preg_replace('/[^a-zA-Z0-9]/', '', $model_route);
		$model = new $class($this->registry);
		
		return $model;
	}
}
?>