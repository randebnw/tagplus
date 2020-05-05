<?php

namespace TagplusBnw\Opencart;

class Attribute extends \TagplusBnw\Opencart\Base {
	
	private $language_id;
	
	public function __construct($registry) {
		parent::__construct($registry);
		$this->language_id = $registry->get('config')->get('config_language_id');
	}
	
	public function get_all() {
		$sql = "SELECT DISTINCT a.attribute_id, a.tgp_id FROM `" . DB_PREFIX . "attribute` a ";
		$result = $this->db->query($sql);
		
		return $result->rows;
	}
	
	public function insert($attribute, $default_group_id) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "attribute SET attribute_group_id = '" . (int)$default_group_id . "', tgp_id = '" . $this->db->escape($attribute['id']) . "', sort_order = '0'");
		
		$attribute_id = $this->db->getLastId();
		
		$this->db->query("INSERT INTO " . DB_PREFIX . "attribute_description SET attribute_id = '" . (int)$attribute_id . "', language_id = '" . (int)$this->language_id . "', name = '" . $this->db->escape($attribute['name']) . "'");
		return $attribute_id;
	}
}
?>