<?php

namespace TagplusBnw\Opencart;

class Product extends \TagplusBnw\Opencart\Base {
	
	private $language_id;
	private $customer_group_id;
	
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
		
		// TODO inserir opcionais
		
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
		
		// TODO atualizar opcionais
		
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