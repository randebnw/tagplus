<?php

namespace TagplusBnw\Opencart;

class Order extends \TagplusBnw\Opencart\Base {
	public function get_products($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");
		return $query->rows;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 5 de mai de 2020
	 * @param unknown $order_id
	 */
	public function get_order_totals($order_id) {
		// recupera apenas fretes e descontos
		$sql = "SELECT * FROM " . DB_PREFIX . "order_total ot ";
		$sql .= "WHERE ot.order_id = '" . (int)$order_id . "' ";
		$sql .= "AND (ot.code = 'shipping' OR ot.value < 0) ";
		
		$query = $this->db->query($sql);
		return $query->rows;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 5 de mai de 2020
	 * @param unknown $order_id
	 */
	public function get_order_products($order_id) {
		$sql = "SELECT op.*, pov.tgp_id AS option_tgp_id ";
		$sql .= "FROM " . DB_PREFIX . "order_product op ";
		$sql .= "LEFT JOIN " . DB_PREFIX . "order_option opt ON opt.order_product_id = op.order_product_id ";
		$sql .= "LEFT JOIN " . DB_PREFIX . "product_option_value pov ON pov.product_option_value_id = opt.product_option_value_id ";
		$sql .= "WHERE op.order_id = '" . (int)$order_id . "' ";
		
		$result = [];
		if ($query->rows) {
			foreach ($query->rows as $row) {
				if (!isset($result[$row['order_product_id']])) {
					$result[$row['order_product_id']] = $row;
				}
				
				// sobrescreve o tgp_id com opcional, se houver
				if (!empty($row['option_tgp_id'])) {
					$result[$row['order_product_id']]['tgp_id'] = $row['option_tgp_id'];
				}
			}
		}
		
		return $result;
	}
	
	/**
	 * Recupera pedidos que serao exportados para a TagPlus
	 * 
	 * @author Rande A. Moreira
	 * @since 15 de abr de 2020
	 */
	public function get_orders_to_export() {
		$sql = "SELECT o.* ";
		$sql .= "FROM `" . DB_PREFIX . "order` o ";
		$sql .= "WHERE o.order_status_id > 0 ";
		if ($this->config->get('tgp_order_status_export')) {
			$sql .= "AND o.order_status_id IN (" . implode(",", $this->config->get('tgp_order_status_export')) . ") ";
		}
		$sql .= "AND (o.tgp_id IS NULL OR o.tgp_id = '') ";
		$result = $this->db->query($sql);
		return $result->rows;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 5 de mai de 2020
	 */
	public function get_paid() {
		$sql = "SELECT o.order_id, o.tgp_id ";
		$sql .= "FROM `" . DB_PREFIX . "order` o ";
		$sql .= "WHERE o.order_status_id = " . implode(',', $this->config->get('config_paid_status_id')) . " ";
		$sql .= "AND o.tgp_id IS NOT NULL AND o.tgp_id != '') ";
		$result = $this->db->query($sql);
		return $result->rows;
	}
}
?>