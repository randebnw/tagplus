<?php

namespace TagplusBnw;

class Helper {
	const STATUS_ATIVO = 1;
	const STATUS_INATIVO = 0;
	
	public static function tgp_product_2_oc_product($tgp_product, $product_config) {
		// extrai as variaveis do array de config
		extract($product_config);
		
		$oc_product['tgp_id'] = $tgp_product['id'];
		$oc_product['name'] = $tgp_product['descricao'];
		$oc_product['status'] = $tgp_product['ativo'];
		$oc_product['sku'] = $tgp_product['codigo'];
		$oc_product['ean'] = $tgp_product['codigo_barras'];
		$oc_product['price'] = $tgp_product['valor_venda_varejo'];
		$oc_product['quantity'] = $tgp_product['qtd_consumo'];
		$oc_product['special'] = '';
		$oc_product['subtract'] = $product_config['subtract'];
		$oc_product['shipping'] = $product_config['shipping'];
		$oc_product['stock_status_id'] = $product_config['stock_status_id'];
		$oc_product['length'] = (float) $tgp_product['comprimento'];
		$oc_product['height'] = (float) $tgp_product['altura'];
		$oc_product['width'] = (float) $tgp_product['largura'];
		$oc_product['length_class_id'] = $product_config['length_class_id'];
		
		$oc_product['weight'] = $tgp_product['peso'];
		$oc_product['weight_class_id'] = $product_config['weight_class_id'];
		$oc_product['date_available'] = date('Y-m-d', strtotime('1 day ago'));
		
		// TODO categorias
		/*if (isset($tgp_product['categoria'])) {
			$oc_product['category']['id'] = $tgp_product['ID_' . $field_cat];
			$oc_product['category']['name'] = $tgp_product['NOME_' . $field_cat];
		}
		
		if (isset($tgp_product['ID_' . $field_subCat], $tgp_product['NOME_' . $field_subCat])) {
			$oc_product['sub_category']['id'] = $tgp_product['ID_' . $field_subCat];
			$oc_product['sub_category']['name'] = $tgp_product['NOME_' . $field_subCat];
		}*/
		
		if (isset($tgp_product['fornecedores']) && !empty($tgp_product['fornecedores'])) {
			foreach ($tgp_product['fornecedores'] as $item) {
				if ($item['fabricante']) {
					$oc_product['manufacturer']['id'] = $item['id'];
					$oc_product['manufacturer']['name'] = $item['nome_fantasia'] ? $item['nome_fantasia'] : $item['razao_social'];
				}
			}
		}
		
		$empty_fields = array(
			'description', 'model', 'sku', 'mpn', 'upc', 'jan', 'isbn', 'location', 
			'minimum', 'points', 'sort_order', 'tax_class_id'
		);
		foreach ($empty_fields as $f) {
			$oc_product[$f] = '';
		}
		
		return $oc_product;
	}	
	
	
	public static function tgp_simple_product_2_oc_product($tgp_product, $product_config) {
		$oc_product['tgp_id'] = $tgp_product['CODIGO'];
		$oc_product['special'] = '';// TODO (float) $tgp_product->precoPromocional;
		
		return $oc_product;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 11 de jan de 2019
	 * @param unknown $oc_customer
	 * @param unknown $tgp_config
	 */
	public static function oc_customer_2_tgp_customer($oc_customer, $tgp_config) {
		$tgp_customer = array();
		
		$tgp_customer['NOME'] = $oc_customer['cpf'] ? ($oc_customer['firstname'] . ' ' . $oc_customer['lastname']) : $oc_customer['razao_social'];
		$tgp_customer['NOME'] = mb_strtoupper($tgp_customer['NOME']);
		
		$tgp_customer['PESSOA'] = $oc_customer['cpf'] ? 'F' : 'J';
		$tgp_customer['CNPJ'] = preg_replace("/[^0-9]/", '', $oc_customer['cnpj']);
		$tgp_customer['CPF'] = preg_replace("/[^0-9]/", '', $oc_customer['cpf']);
		$tgp_customer['INSCEST'] = $oc_customer['inscricao_estadual'];
		
		$tgp_customer['DTNASC'] = $oc_customer['data_nascimento'];
		if (strpos($oc_customer['data_nascimento'], '/') !== false) {
			$tgp_customer['DTNASC'] = self::view_date_2_db_date($oc_customer['data_nascimento']);
		}
		
		$tgp_customer['DTCAD'] = date('Y-m-d H:i:s');
		$tgp_customer['SITUACAO'] = self::SITUACAO_ATIVO;
		$tgp_customer['EMPRESA'] = $tgp_config['tgp_default_customer_company'];
		$tgp_customer['OPCAD'] = $tgp_config['tgp_order_seller_name'];
		$tgp_customer['VENDEDOR'] = $tgp_config['tgp_order_seller_code'];
		$tgp_customer['NIVEL'] = $tgp_config['tgp_default_customer_level'];
		$tgp_customer['CONCEITO'] = $tgp_config['tgp_default_customer_group'];
		$tgp_customer['CODCEN'] = $tgp_config['tgp_default_center_code'];
		$tgp_customer['CODSUB'] = $tgp_config['tgp_default_subcenter_code'];
		$tgp_customer['PLANCON'] = (string) $tgp_config['tgp_default_billing_plan'];
		
		$tgp_customer['OBS'] = 'Cliente cadastrado através da loja virtual.';
		$tgp_customer['FANTASIA'] = '';
		$tgp_customer['IDENT'] = '';
		$tgp_customer['INSCMUN'] = '';
		
		return $tgp_customer;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 11 de jan de 2019
	 * @param unknown $oc_address
	 * @param unknown $tgp_config
	 */
	public static function oc_address_2_tgp_address($oc_address, $tgp_config, $list_zones) {
		$tgp_address = array();
	
		$tgp_address['DTCAD'] = date('Y-m-d H:i:s');
		$tgp_address['OPCAD'] = $tgp_config['tgp_order_seller_name'];
		$tgp_address['BAIRRO'] = $oc_address['address_2'];
		$tgp_address['CEP'] = $oc_address['postcode'];
		$tgp_address['CIDADE'] = $oc_address['city'];
		$tgp_address['COMP'] = $oc_address['complemento'];
		$tgp_address['EMAIL'] = $oc_address['email'];
		$tgp_address['END'] = $oc_address['address_1'];
		$tgp_address['ESTADO'] = isset($list_zones[$oc_address['zone_id']]) ? $list_zones[$oc_address['zone_id']] : '';
		$tgp_address['FONE'] = preg_replace("/[^0-9]/", '', $oc_address['telephone']);
		$tgp_address['CELULAR'] = preg_replace("/[^0-9]/", '', $oc_address['fax']);
		$tgp_address['NUM'] = $oc_address['numero'];
		$tgp_address['PAIS'] = 'BRASIL';
		
		$tgp_address['FONEAUX'] = '';
	
		return $tgp_address;
	}
	
	/**
	 * 
	 * @param unknown $tgp_customer
	 * @param unknown $tgp_config
	 * @param unknown $list_zones
	 * @return multitype:string number mixed NULL unknown
	 */
	public static function tgp_customer_2_oc_customer($tgp_customer, $tgp_config, $config, $list_zones) {
		$oc_customer = array();
		
		$tgp_address = $tgp_customer['address'];
		$tgp_customer = $tgp_customer['customer'];
	
		$names = explode(' ', $tgp_customer['NOME']);
		$oc_customer['firstname'] = array_shift($names);
		$oc_customer['lastname'] = '';
		if ($names) {
			$oc_customer['lastname'] = implode(' ', $names);
		}
		
		$oc_customer['doing_import'] = 1;
		$oc_customer['tgp_id'] = $tgp_customer['CODIGO'];
		$oc_customer['cpf'] = '';
		$oc_customer['cnpj'] = '';
		$oc_customer['company'] = '';
		$oc_customer['company_id'] = '';
		$oc_customer['tax_id'] = '';
		$oc_customer['razao_social'] = '';
		$oc_customer['sexo'] = '';
		$oc_customer['rg'] = '';
		$oc_customer['apelido'] = '';
		if ($tgp_customer['CPF']) {
			$oc_customer['cpf'] = preg_replace("/[^0-9]/", '', $tgp_customer['CPF']);
			$oc_customer['password'] = $oc_customer['cpf'];
		} else if ($tgp_customer['CNPJ']) {
			$oc_customer['cnpj'] = preg_replace("/[^0-9]/", '', $tgp_customer['CNPJ']);
			$oc_customer['razao_social'] = $tgp_customer['NOME'];
			$oc_customer['password'] = $oc_customer['cnpj'];
		}
		
		$oc_customer['data_nascimento'] = date('d/m/Y', strtotime($tgp_customer['DTNASC']));
		$oc_customer['inscricao_estadual'] = $tgp_customer['INSCEST'];
		$oc_customer['customer_group_id'] = $tgp_customer['CONCEITO'];
		$oc_customer['email'] = $tgp_address['EMAIL'];
		$oc_customer['telephone'] = preg_replace("/[^0-9]/", '', $tgp_address['FONE']);
		$oc_customer['fax'] = preg_replace("/[^0-9]/", '', $tgp_address['CELULAR']);
		$oc_customer['newsletter'] = 1;
		
		$oc_customer['address_1'] = $tgp_address['END'];
		$oc_customer['address_2'] = $tgp_address['BAIRRO'];
		$oc_customer['postcode'] = $tgp_address['CEP'];
		$oc_customer['city'] = $tgp_address['CIDADE'];
		$oc_customer['complemento'] = $tgp_address['COMP'];
		$oc_customer['numero'] = $tgp_address['NUM'];
		
		$oc_customer['zone_id'] = $config->get('config_zone_id');
		foreach ($list_zones as $zone_id => $code) {
			if ($code == $tgp_address['ESTADO']) {
				$oc_customer['zone_id'] = $zone_id;
				break;
			}			
		}
		
		$oc_customer['country_id'] = $config->get('config_country_id');
	
		$oc_customer = array_map('utf8_encode', $oc_customer);
		return $oc_customer;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 11 de jan de 2019
	 * @param unknown $oc_order
	 * @param unknown $oc_products
	 * @param unknown $company
	 * @param unknown $shipping
	 * @param unknown $discount
	 * @param unknown $customer
	 * @return array
	 */
	public static function oc_order_2_tgp_order($oc_order, $oc_products, $company, $operation_code, $shipping, $discount, $customer, $tgp_config) {
		$tgp_order = array();
		
		$tgp_order['CODCLI'] = $customer['tgp_id'];
		$tgp_order['CODEMP'] = $company['company_id'];
		$tgp_order['OBSADD'] = $oc_order['comment'];
		if ($company['zone_code'] == $customer['uf']) {
			$tgp_order['NATUREZA'] = $company['order_type_zone_in'];
		} else {
			$tgp_order['NATUREZA'] = $company['order_type_zone_out'];
		}
		
		$tgp_order['STATUS'] = $tgp_config['tgp_order_status_new'];
		$tgp_order['OPCAD'] = $tgp_config['tgp_order_seller_name'];
		$tgp_order['VEND'] = $tgp_config['tgp_order_seller_code'];
		$tgp_order['TIPODESC'] = $operation_code;
		$tgp_order['CONDPAG'] = $oc_order['payment_condition'];
		$tgp_order['CODTEC'] = $tgp_config['tgp_order_codtec'];
		$tgp_order['OPERACAO'] = $tgp_config['tgp_order_operation_number'];
		$tgp_order['PREST'] = $tgp_config['tgp_order_prest_serv'];
		
		$tgp_order['VLRDESCONTO'] = $discount;
		$tgp_order['VLRFRETE'] = $shipping;
		$tgp_order['VLRFRETE2'] = $shipping;
		
		$tgp_order['DTCAD'] = date('Y-m-d H:i:s');
		$tgp_order['DATA'] = date('Y-m-d H:i:s');
		
		$obs = 'Pedido cadastrado pela loja virtual (Web).';
		$obs .= ' Frete Escolhido: ' . $oc_order['shipping_method'];
		$obs .= ' Valor do Frete: ' . $shipping;
		$tgp_order['OBS'] = $obs;
		
		$tgp_itens = array();
		$total_shipping_itens = 0;
		foreach ($oc_products as $index => $product) {
			$tgp_item = array();
			$price_info = $product['tgp_product_price'];
			$price_unit = $product['is_special'] ? $product['price'] : $price_info['VALOR'];
			
			$product_total = round($price_unit * $product['quantity']);
			$taxes = self::_calculate_product_taxes($product_total, $price_info);
			
			$tgp_item['CODEMP'] = $company['company_id'];
			$tgp_item['OPCAD'] = $tgp_config['tgp_order_seller_name'];
			$tgp_item['NATUREZA'] = $tgp_order['NATUREZA'];
			$tgp_item['TABELA'] = $customer['customer_group_id'];
			$tgp_item['PRODUTO'] = $product['tgp_id'];
			$tgp_item['PRUNIT'] = $price_unit;
			$tgp_item['VLRDESC'] = $product['discount'];
			$tgp_item['QTPROD'] = $product['quantity'];
			$tgp_item['PRBACKUP'] = $price_unit;
			$tgp_item['TOTVALOR'] = $product_total;
			
			$tgp_item['CUSTO'] = $product['custo'];
			$tgp_item['CUSTOCOMPRA'] = $product['custo_compra'];
			$tgp_item['TIPODESC'] = $tgp_order['TIPODESC'];
			
			$tgp_item['ICMS'] = $price_info['ICMS'];
			$tgp_item['PERCIPI'] = $price_info['IPI'];
			$tgp_item['BASERED'] = $price_info['BASERED'];
			$tgp_item['VLRIPI'] = $price_info['VALORIPI'];
			$tgp_item['VLRST'] = $price_info['VALORST'];
			$tgp_item['COMISSAO'] = $price_info['COMISSAO'];
			
			$tgp_item['BASEST'] = $taxes['base_st'];
			$tgp_item['VLRICMS'] = $taxes['vlr_icms'];
			$tgp_item['PERCST'] = $taxes['perc_st'];
			$tgp_item['BASEICMS'] = $taxes['base_icms'];
			
			$is_last = ($index == (count($oc_products) - 1));
			$item_shipping = self::get_item_shipping($is_last, $oc_order['total'], $tgp_item['TOTVALOR'], $shipping, $total_shipping_itens);
			$total_shipping_itens += $item_shipping;
			
			$tgp_item['VLRFRETE'] = $item_shipping;
			$tgp_item['VLRFRETEB'] = $item_shipping;
			$tgp_item['DTCAD'] = date('Y-m-d H:i:s');
			
			$tgp_itens[] = $tgp_item;
		}
		
		return array('order' => $tgp_order, 'itens' => $tgp_itens);
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 8 de fev de 2019
	 * @param unknown $total
	 * @param unknown $price_info
	 */
	private static function _calculate_product_taxes($total, $price_info) {
		$taxes = array(
			'base_st'   => 0.00,
			'perc_st'   => 0.00,
			'vlr_icms'  => 0.00,
			'base_icms' => 0.00
		);
		
		if ($price_info['MVA'] > 0) {
			$taxes['base_st'] = $total * (1 + ($price_info['MVA'] / 100));
			$taxes['perc_st'] = $price_info['MVA'];
		}
		
		if ($price_info['ICMS2'] > 0) {
			$taxes['vlr_icms'] = ($total * $price_info['ICMS2']) / 100;
			$taxes['base_icms'] = $total;
		}
		
		return $taxes;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 16 de jan de 2019
	 * @param unknown $is_last
	 * @param unknown $order_total
	 * @param unknown $item_total
	 * @param unknown $total_shipping
	 * @param unknown $total_shipping_itens
	 */
	private static function get_item_shipping($is_last, $order_total, $item_total, $total_shipping, $total_shipping_itens) {
		$shipping = 0;
		if (!$is_last) {
			// se nao for o ultimo, calcula o frete proporcional ao valor do pedido
			$shipping = round($item_total / $order_total * $total_shipping, 2);
		} else {
			// se for o ultimo, só ve qto falta pra completar o valor total do frete
			$shipping = $total_shipping - $total_shipping_itens;
		}
		
		return $shipping;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 11 de dez de 2018
	 * @param unknown $tgp_company
	 */
	public static function tgp_company_2_oc_company($tgp_data) {
		$oc_data = array();
		$oc_data['company_id'] 	= $tgp_data['CODIGO'];
		$oc_data['name'] 		= $tgp_data['NOME'];
		
		return $oc_data;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 11 de dez de 2018
	 * @param unknown $tgp_customer_group
	 */
	public static function tgp_customer_group_2_oc_customer_group($tgp_data) {
		$oc_data = array();
		$oc_data['customer_group_id'] 	= $tgp_data['CODIGO'];
		$oc_data['name'] 				= $tgp_data['NOME'];
	
		return $oc_data;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 11 de dez de 2018
	 * @param unknown $tgp_payment_condition
	 */
	public static function tgp_payment_condition_2_oc_payment_condition($tgp_data) {
		$oc_data = array();
		$oc_data['payment_id'] 	= $tgp_data['CODIGO'];
		$oc_data['name'] 		= $tgp_data['NOME'];
		
		return $oc_data;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 14 de jun de 2019
	 * @param unknown $tgp_data
	 */
	public static function tgp_order_status_2_oc_order_status($tgp_data) {
		$oc_data = array();
		$oc_data['status_id'] 	= $tgp_data['CODIGO'];
		$oc_data['name'] 		= utf8_encode($tgp_data['NOME']);
	
		return $oc_data;
	}
	
	public static function oc_datetime_2_tgp_datetime($datetime) {
		list($date, $time) = explode(' ', $datetime);
		list($year, $month, $day) = explode('-', $date);
		
		return array(
			'date' => $day . '/' . $month . '/' . $year,
			'time' => $time
		);
	}
	
	public static function tgp_date_2_oc_date($date) {
		list($day, $month, $year) = explode('/', $date);
		return $year . '-' . $month . '-' . $day;
	}
	
	public static function view_date_2_db_date($date) {
		list($day, $month, $year) = explode('/', $date);
		return $year . '-' . $month . '-' . $day;
	}
	
	public static function cube_root($n) {
		return pow($n, 1/3);
	}
}
?>