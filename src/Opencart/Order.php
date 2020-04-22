<?php

namespace TagplusBnw\Opencart;

class Order extends \TagplusBnw\Opencart\Base {
	public function get_products($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");
		return $query->rows;
	}
	
	public function get_totals($order_id) {
		// recupera apenas fretes e descontos
		$sql = "SELECT * FROM " . DB_PREFIX . "order_total ot ";
		$sql .= "WHERE ot.order_id = '" . (int)$order_id . "' ";
		$sql .= "AND (ot.code = 'shipping' OR ot.value < 0) ";
		
		$query = $this->db->query($sql);
		return $query->rows;
	}
	
	public function get_order_products($order_id) {
		// TODO recuperar produtos do pedido (considerar opcional)
		$sql = "SELECT * FROM " . DB_PREFIX . "order_total ot ";
		$sql .= "WHERE ot.order_id = '" . (int)$order_id . "' ";
		$sql .= "AND (ot.code = 'shipping' OR ot.value < 0) ";
	
		$query = $this->db->query($sql);
		return $query->rows;
	}
	
	/**
	 * Exporta apenas pedidos pagos para a TagPlus
	 * 
	 * @author Rande A. Moreira
	 * @since 15 de abr de 2020
	 */
	public function get_orders_to_export() {
		$sql = "SELECT o.* ";
		$sql .= "FROM `" . DB_PREFIX . "order` o ";
		$sql .= "WHERE o.order_status_id > 0 ";
		$sql .= "AND o.order_status_id = " . (int) $this->config->get('config_paid_status_id') . " ";
		$sql .= "AND (o.tgp_id IS NULL OR o.tgp_id = '') ";
		$result = $this->db->query($sql);
		return $result->rows;
	}
	
	public function get_paid() {
		$sql = "SELECT o.order_id, ";
		$sql .= "( ";
		$sql .= "	SELECT GROUP_CONCAT(o2d.dc_id SEPARATOR '#') FROM " . DB_PREFIX . "order_2_dc_order o2d ";
		$sql .= "	WHERE o2d.order_id = o.order_id AND o2d.dc_status = '" . $this->config->get('dc_order_status_new') . "'";
		$sql .= "	GROUP BY o.order_id ";
		$sql .= ") AS dc_ids ";
		$sql .= "FROM `" . DB_PREFIX . "order` o ";
		$sql .= "WHERE o.order_status_id > 0 ";
		$sql .= "AND o.order_status_id IN (" . implode(',', $this->config->get('config_paid_status_id')) . ")";
		$result = $this->db->query($sql);
		return $result->rows;
	}
}
?>