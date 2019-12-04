<?php

namespace TagplusBnw\Opencart;

use TagplusBnw;

class Api extends \TagplusBnw\Opencart\Base {
	
	const METHOD_GET = 'GET';
	const ALLOWED_PRODUCT_TYPES = array('N', 'G');
	
	private $cart;
	private $load;
	
	private $api;
	
	/**
	 * 
	 * @var Config
	 */
	private $config;
	
	/**
	 * 
	 * @var Product
	 */
	private $model_product;
	
	/**
	 * 
	 * @var Order
	 */
	private $model_order;
	
	/**
	 * 
	 * @var Category
	 */
	private $model_category;
	
	/**
	 * 
	 * @var Manufacturer
	 */
	private $model_manufacturer;
	
	private $list_companies;
	private $list_companies_info;
	private $list_customer_groups;
	private $list_zones;
	private $list_country;
	private $list_payment_conditions;
	private $map_categories;
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
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 8 de jan de 2019
	 * @param unknown $registry
	 */
	public static function get_instance($registry) {
		if (self::$instance == null) {
			self::$instance = new \TagplusBnw\Opencart\Api($registry);
			self::$instance->model_product = new \TagplusBnw\Opencart\Product($registry);
			self::$instance->model_category = new \TagplusBnw\Opencart\Category($registry);
			self::$instance->model_manufacturer = new \TagplusBnw\Opencart\Manufacturer($registry);
			self::$instance->model_order = new \TagplusBnw\Opencart\Order($registry);
			self::$instance->config = new \TagplusBnw\Opencart\Config($registry->get('config'));
		}
		
		return self::$instance;
	}
	
	public function init_maps() {
		if (!$this->map_product || !$this->map_categories || !$this->map_manufacturer) {
			$categories = $this->model_category->get_all();
			foreach ($categories as $item) {
				if ($item['tgp_type'] == 'group') {
					$this->map_categories[$item['tgp_id']] = $item['category_id'];
				} else {
					$this->map_sub_categories[$item['parent_id']][$item['tgp_id']] = $item['category_id'];
				}
			}
			
			$manufacturers = $this->model_manufacturer->get_all();
			foreach ($manufacturers as $item) {
				$this->map_manufacturer[$item['tgp_id']] = $item['manufacturer_id'];
			}
			
			$products = $this->model_product->get_all();
			foreach ($products as $item) {
				$this->map_product[$item['tgp_id']] = $item['product_id'];
			}	
		}
	}
	
	public function init_reverse_maps() {
		$products = $this->model_product->get_all();
		foreach ($products as $item) {
			$this->map_product[$item['product_id']] = $item['tgp_id'];
		}
	}
	
	private function _init_list_zones() {
		if (!$this->list_zones) {
			// estado de destino
			$model_zone = $this->_load_model('localisation/zone');
			$zones = $model_zone->getZonesByCountryId($this->config->get('config_country_id'));
			
			if ($zones) {
				foreach ($zones as $item) {
					$this->list_zones[$item['zone_id']] = $item['code'];
				}
			}	
		}
	}
	
	public function init_dependencies() {
		if ($this->list_payment_conditions) {
			// listas ja inicializadas
			return;
		}
		
		// condicoes de pagamento
		$payment_conditions = $this->config->get(self::PAYMENT_CONDITIONS_CONFIG);
		if ($payment_conditions) {
			foreach ($payment_conditions as $item) {
				$this->list_payment_conditions[] = $item['id'];
			}
		}
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 6 de dez de 2018
	 * @param unknown $item
	 */
	public function import_product($item) {
		if (is_null($this->map_product)) {
			$this->init_maps();
			$this->init_dependencies();
		}
		
		$this->error = '';
		
		// importa categoria se ainda nao existir
		if (isset($item['category']['id']) && !isset($this->map_categories[$item['category']['id']])) {
			if ($cat_id = $this->model_category->insert($item['category'])) {
				$this->map_categories[$item['category']['id']] = $cat_id;
			} else {
				$this->error = 'Erro ao importar categoria ' . $item['category']['id'];
				\TagplusBnw\Util\Log::error($this->error);
				throw new \Exception($this->error);
			}
		}
		
		// importa subcategoria se ainda nao existir
		$parent_id = $this->map_categories[$item['category']['id']];
		if (isset($item['sub_category']['id']) && !isset($this->map_sub_categories[$parent_id][$item['sub_category']['id']])) {
			if ($cat_id = $this->model_category->insert($item['sub_category'], $parent_id)) {
				$this->map_sub_categories[$parent_id][$item['sub_category']['id']] = $cat_id;
			} else {
				$this->error = 'Erro ao importar sub-categoria ' . $item['sub_category']['id'];
				\TagplusBnw\Util\Log::error($this->error);
				throw new \Exception($this->error);
			}
		}
		
		// importa fabricante se ainda nao existir
		if (isset($item['manufacturer']['id']) && !isset($this->map_manufacturer[$item['manufacturer']['id']])) {
			if ($manufacturer_id = $this->model_manufacturer->insert($item['manufacturer'])) {
				$this->map_manufacturer[$item['manufacturer']['id']] = $manufacturer_id;
			} else {
				$this->error = 'Erro ao importar fabricante ' . $item['manufacturer']['id'];
				\TagplusBnw\Util\Log::error($this->error);
				throw new \Exception($this->error);
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
			$this->model_product->update($product_id, $item);
		} else {
			// INSERT
			$product_id = $this->model_product->insert($item);
			if ($product_id) {
				$this->map_product[$item['tgp_id']] = $product_id;
			}
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
	public function simple_update_product($product_id) {
		if ($product) {
			$product_config = $this->config->get_default_product_config();
			$product = \TagplusBnw\Helper::tgp_simple_product_2_oc_product($product, $product_config);
			$this->model_product->simple_update($product);
		}
		
		return false;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 6 de dez de 2018
	 * @param unknown $tgp_id
	 */
	public function synchronize_product($product) {
		if ($product) {
			$product_config = $this->config->get_default_product_config();
			$product = \TagplusBnw\Helper::tgp_product_2_oc_product($product, $product_config);
			return $this->import_product($product);
		}
		
		return false;
	}
	
	/**
	 * 
	 * @param unknown $tgp_id
	 * @return string
	 */
	public function import_customer($tgp_customer) {
		if ($tgp_customer) {
			$this->_init_list_zones();
			
			$model_setting = $this->_load_model('setting/setting');
			$tgp_config = $model_setting->getSetting('tagplus');
			
			$oc_customer = \TagplusBnw\Helper::tgp_customer_2_oc_customer($tgp_customer, $tgp_config, $this->config, $this->list_zones);
			
			$model_customer = $this->_load_model('account/customer');
			$customer_exists = $model_customer->getCustomerByEmail($oc_customer['email']);
			if ($customer_exists) {
				return false;
			}
			
			$customer_id = $model_customer->addCustomer($data);
			return $customer_id;
		}
		
		return false;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 8 de jan de 2019
	 * @param unknown $order
	 */
	public function convert_order($order, $cart) {
		$totals = $order['totals'];
		$products = $order['products'];
		
		$customer_address = $this->model->get_customer_address($this->customer->getId());
		$customer = array(
			'tgp_id' => $this->customer->getDcId(),
			'customer_group_id' => $this->customer->getCustomerGroupId(),
			'uf' => $customer_address['uf']
		);
		
		$model_setting = $this->_load_model('setting/setting');
		$tgp_config = $model_setting->getSetting('tagplus');
		
		if ($this->config->get($order['payment_code'] . '_tgp_payment_condition')) {
			$order['payment_condition'] = $this->config->get($order['payment_code'] . '_tgp_payment_condition');
		} else {
			throw new Exception('Condição de pagamento não configurada para o meio de pagamento: ' . $order['payment_code'] . ' - ' . $order['payment_method']);
		}
		
		// calcula soma do valor dos produtos
		$total_products = $this->_get_total_products($products);
		
		$total_info = $this->_get_single_shipping_and_discount($order['total'], $total_products, $totals, $products);
		$tgp_orders = TagplusBnw\Helper::oc_order_2_tgp_order(
			$order, $total_info['products'], $this->map_company[$company_id], $cart->get_operation_code(),
			$total_info['shipping'], $total_info['total_discount'], 
			$customer, $tgp_config
		);
		
		if ($this->config->get('tgp_debug')) {
			\TagplusBnw\Util\Log::debug('ADD_ORDER > ' . print_r($tgp_orders, true));
		}
		
		return $tgp_order;
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
				$oc_companies[] = \TagplusBnw\Helper::tgp_company_2_oc_company($item);
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
				$oc_groups[] = \TagplusBnw\Helper::tgp_customer_group_2_oc_customer_group($item);
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
	public function import_payment_conditions($payment_conditions) {
		if ($payment_conditions) {
			$model_setting = $this->_load_model('setting/setting');
			$model_setting->editSettingValue('tgp_payment_conditions', self::PAYMENT_CONDITIONS_CONFIG, $payment_conditions);
			
			return $payment_conditions;
		}
		
		return false;
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
				$oc_order_status[] = \TagplusBnw\Helper::tgp_order_status_2_oc_order_status($item);
			}
		}
	
		if ($oc_order_status) {
			$model_setting = $this->_load_model('setting/setting');
			$model_setting->editSettingValue('tagplus_order_status', self::ORDER_STATUS_CONFIG, $oc_order_status);
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
	 * Essa funcao sempre carrega o model do catalog/
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