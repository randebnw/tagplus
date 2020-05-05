<?php

namespace TagplusBnw\Opencart;

class Product extends \TagplusBnw\Opencart\Base {
	
	private $language_id;
	private $customer_group_id;
	
	// TODO ao salvar produtos pelo painel, recuperar informacoes de tgp_id dos opcionais
	public function __construct($registry) {
		parent::__construct($registry);
		$this->language_id = $registry->get('config')->get('config_language_id');
		$this->customer_group_id = $registry->get('config')->get('config_customer_group_id');
	}
	
	public function get_all() {
		$sql = "SELECT product_id, tgp_id FROM `" . DB_PREFIX . "product` WHERE tgp_id IS NOT NULL AND tgp_id > 0 ";
		$result = $this->db->query($sql);
		
		return $result->rows;
	}
	
	public function insert($data) {
		\TagplusBnw\Util\Log::debug('NEW PRODUCT INSERT > ' . $data['tgp_id']);
		
		$sql = "INSERT INTO " . DB_PREFIX . "product ";
		$sql .= "SET tgp_id = '" . $this->db->escape($data['tgp_id']) . "', ";
		$sql .= "model = '" . $this->db->escape($data['model']) . "', ";
		$sql .= "sku = '" . $this->db->escape($data['sku']) . "', ";
		$sql .= "upc = '" . $this->db->escape($data['upc']) . "', ";
		$sql .= "ean = '" . $this->db->escape($data['ean']) . "', ";
		$sql .= "jan = '" . $this->db->escape($data['jan']) . "', ";
		$sql .= "isbn = '" . $this->db->escape($data['isbn']) . "', ";
		$sql .= "mpn = '" . $this->db->escape($data['mpn']) . "', ";
		$sql .= "location = '" . $this->db->escape($data['location']) . "', ";
		$sql .= "quantity = '" . (int)$data['quantity'] . "', ";
		$sql .= "minimum = '" . (int)$data['minimum'] . "', ";
		$sql .= "subtract = '" . (int)$data['subtract'] . "', ";
		$sql .= "stock_status_id = '" . (int)$data['stock_status_id'] . "', ";
		$sql .= "date_available = '" . $this->db->escape($data['date_available']) . "', ";
		$sql .= "manufacturer_id = '" . (int)$data['manufacturer_id'] . "', ";
		$sql .= "shipping = '" . (int)$data['shipping'] . "', ";
		$sql .= "price = '" . (float)$data['price'] . "', ";
		$sql .= "points = '" . (int)$data['points'] . "', ";
		$sql .= "weight = '" . (float)$data['weight'] . "', ";
		$sql .= "weight_class_id = '" . (int)$data['weight_class_id'] . "', ";
		$sql .= "length = '" . (float)$data['length'] . "', ";
		$sql .= "width = '" . (float)$data['width'] . "', ";
		$sql .= "height = '" . (float)$data['height'] . "', ";
		$sql .= "length_class_id = '" . (int)$data['length_class_id'] . "', ";
		$sql .= "status = '" . (int)$data['status'] . "', ";
		$sql .= "tax_class_id = '" . $this->db->escape($data['tax_class_id']) . "', ";
		$sql .= "sort_order = '" . (int)$data['sort_order'] . "', date_added = NOW(), api_modified = NOW() ";
		
		$this->db->query($sql);
		
		$product_id = $this->db->getLastId();
		
		$sql = "INSERT INTO " . DB_PREFIX . "product_description ";
		$sql .= "SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$this->language_id . "', ";
		$sql .= "name = '" . $this->db->escape($data['name']) . "', ";
		$sql .= "meta_keyword = '', meta_description = '', ";
		$sql .= "description = '" . $this->db->escape($data['description']) . "', ";
		$sql .= "tag = ''";
		$this->db->query($sql);
		
		$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = 0");
		
		foreach ($data['categories'] as $category_id) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "', is_tgp = 1");
		}
		
		// OPCIONAIS
		if (isset($data['options']) && $data['options']) {
			foreach ($data['options'] as $option_id => $values) {
				$this->db->query("
					INSERT INTO " . DB_PREFIX . "product_option SET 
					product_id = '" . (int)$product_id . "', 
					option_id = '" . (int)$option_id . "', 
					required = 1
				");
				
				$product_option_id = $this->db->getLastId();
				foreach ($values as $product_option_value) {
					$this->db->query("
						INSERT INTO " . DB_PREFIX . "product_option_value SET 
						product_option_id = '" . (int)$product_option_id . "', 
						product_id = '" . (int)$product_id . "',
						option_sku = '" . $this->db->escape($product_option_value['sku']) . "',
						tgp_id = '" . $this->db->escape($product_option_value['tgp_id']) . "',
						option_id = '" . (int)$option_id . "', 
						option_value_id = '" . (int)$product_option_value['id'] . "', 
						quantity = " . (int)$product_option_value['quantity'] . ", 
						subtract = " . (int)$data['subtract'] . ", 
						price = '0.00', price_prefix = '+', points = 0, points_prefix = '+', 
						weight = '0.00', weight_prefix = '+'
					");
				}
			}
		}
		
		// ATRIBUTOS
		if (isset($data['attributes'])) {
			foreach ($data['attributes'] as $attr) {
				if ($attr['attribute_id']) {
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$attr['attribute_id'] . "'");
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$attr['attribute_id'] . "', language_id = '" . (int)$this->language_id . "', text = '" .  $this->db->escape($attr['value']) . "'");
				}
			}
		}
		
		// define url amigavel
		$keyword = $this->url->str2url($data['name']);
		$keyword = $this->check_keyword($keyword, $product_id, 'p');
		if ($keyword) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape($keyword) . "'");
		}
		
		$this->cache->delete('product');
		return $product_id;
	}
	
	public function update($product_id, $data) {
		// DADOS GERAIS
		$sql = "UPDATE " . DB_PREFIX . "product ";
		$sql .= "SET model = '" . $this->db->escape($data['model']) . "', ";
		$sql .= "sku = '" . $this->db->escape($data['sku']) . "', ";
		$sql .= "upc = '" . $this->db->escape($data['upc']) . "', ";
		$sql .= "ean = '" . $this->db->escape($data['ean']) . "', ";
		$sql .= "jan = '" . $this->db->escape($data['jan']) . "', ";
		$sql .= "isbn = '" . $this->db->escape($data['isbn']) . "', ";
		$sql .= "mpn = '" . $this->db->escape($data['mpn']) . "', ";
		$sql .= "location = '" . $this->db->escape($data['location']) . "', ";		
		$sql .= "minimum = '" . (int)$data['minimum'] . "', ";
		$sql .= "manufacturer_id = '" . (int)$data['manufacturer_id'] . "', ";
		$sql .= "weight = '" . (float)$data['weight'] . "', ";
		$sql .= "weight_class_id = '" . (int)$data['weight_class_id'] . "', ";
		$sql .= "length = '" . (float)$data['length'] . "', ";
		$sql .= "width = '" . (float)$data['width'] . "', ";
		$sql .= "height = '" . (float)$data['height'] . "', ";
		$sql .= "length_class_id = '" . (int)$data['length_class_id'] . "', ";
		$sql .= "status = '" . (int)$data['status'] . "', ";
		$sql .= "tax_class_id = '" . $this->db->escape($data['tax_class_id']) . "', ";
		$sql .= "api_modified = NOW() ";
		$sql .= "WHERE product_id = '" . (int)$product_id . "'";
		$this->db->query($sql);
		
		// NOME/DESCRICAO
		$sql = "UPDATE " . DB_PREFIX . "product_description ";
		$sql .= "SET name = '" . $this->db->escape($data['name']) . "' ";
		
		// se a atualizacao da descricao nao eh feita via ecommerce, entao atualiza quando vier do ERP
		if (!$this->config->get('tgp_edit_description')) {
			$sql .= ", description = '" . $this->db->escape($data['description']) . "' ";
		}
		
		$sql .= "WHERE product_id = '" . (int)$product_id . "' ";
		$sql .= "AND language_id = '" . (int)$this->language_id . "' ";
		$this->db->query($sql);
		
		// CATEGORIAS
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "' AND is_tgp = 1");
		foreach ($data['categories'] as $category_id) {
			$this->db->query("REPLACE INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "', is_tgp = 1");
		}
		
		// OPCIONAIS
		if (isset($data['options']) && $data['options']) {
			$product_options = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value WHERE product_id = " . $product_id);
			$existing_options = [];
			$map_product_option_id = [];
			$options_values_id = [];
			foreach ($product_options->rows as $item) {
				foreach ($data['options'] as $option_id => $values) {
					if ($item['option_id'] == $option_id) {
						$map_product_option_id[$option_id] = $item['product_option_id'];
						foreach ($values as $key => $v) {
							if ($v['id'] == $item['option_value_id']) {
								$data['options'][$option_id]['values'][$key]['product_option_value_id'] = $item['product_option_value_id'];
							}
						}
						
						break;
					}
				}
			}
			
			foreach ($data['options'] as $option_id => $values) {
				if (!isset($map_product_option_id[$option_id])) {
					// se ainda nao existe o opcional, eh preciso incluir
					$this->db->query("
						INSERT INTO " . DB_PREFIX . "product_option SET
						product_id = '" . (int)$product_id . "',
						option_id = '" . (int)$option_id . "',
						required = 1
					");
					
					$product_option_id = $this->db->getLastId();
				} else {
					$product_option_id = $map_product_option_id[$option_id];
				}
				
				foreach ($values as $product_option_value) {
					// se ainda nao existe na loja, inclui
					if (!isset($product_option_value['product_option_value_id'])) {
						$sql = "
							INSERT INTO " . DB_PREFIX . "product_option_value SET
							product_option_id = '" . (int)$product_option_id . "',
							product_id = '" . (int)$product_id . "',
							option_sku = '" . $this->db->escape($product_option_value['sku']) . "',
							tgp_id = '" . $this->db->escape($product_option_value['tgp_id']) . "',
							option_id = '" . (int)$option_id . "',
							option_value_id = '" . (int)$product_option_value['id'] . "',
							quantity = " . (int)$product_option_value['quantity'] . ",
							subtract = " . (int)$data['subtract'] . ",
							price = '0.00', price_prefix = '+', points = 0, points_prefix = '+',
							weight = '0.00', weight_prefix = '+'
						";
					} else {
						// se ja existe, soh faz o update
						$sql = "
							UPDATE " . DB_PREFIX . "product_option_value SET
							quantity = " . (int)$product_option_value['quantity'] . ",
							WHERE product_option_value_id = " . $product_option_value['product_option_value_id'];
						
						$options_values_id[] = $product_option_value['product_option_value_id'];
					}
					
					$this->db->query($sql);
				}
				
				// exclui opcoes que nao existem mais
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE option_value_id NOT IN (" . implode(",", $options_values_id) . ") ");
			}
		}
		
		// ATRIBUTOS
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "'");
		if (isset($data['attributes'])) {
			foreach ($data['attributes'] as $attr) {
				if ($attr['attribute_id']) {
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$attr['attribute_id'] . "'");
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$attr['attribute_id'] . "', language_id = '" . (int)$this->language_id . "', text = '" .  $this->db->escape($attr['value']) . "'");
				}
			}
		}
		
		$this->cache->delete('product');
	}
	
	public function simple_update($product_id, $data) {
		$sql = "UPDATE " . DB_PREFIX . "product ";
		$sql .= "SET quantity = '" . (int)$data['quantity'] . "', ";
		$sql .= "price = '" . (float)$data['price'] . "', ";
		$sql .= "`status` = '" . (int)$data['status'] . "', ";
		$sql .= "api_modified = NOW() ";
		$sql .= "WHERE product_id = '" . (int)$product_id . "' ";
		$this->db->query($sql);
		
		/*$this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = " . (int)$product_id);
		if ($data['special'] > 0) {
			$sql = "INSERT INTO " . DB_PREFIX . "product_special ";
			$sql .= "SET product_id = '" . (int)$product_id . "', ";
			$sql .= "customer_group_id = '" . (int)$this->customer_group_id . "', ";
			$sql .= "price = '" . (float)$data['special'] . "', ";
			$sql .= "priority = '1', date_start = '', date_end = '' ";
			$this->db->query($sql);
		}*/
		
		$this->cache->delete('product');
	}
}
?>