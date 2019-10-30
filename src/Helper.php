<?php

namespace TagplusBnw;

class Helper {
	const SITUACAO_ATIVO = "A";
	const SITUACAO_INATIVO = "I";
	
	const TAG_COPIA = '[copia]';
	const TAG_CORES = '[cores]';
	const TAG_FAX = '[fax]';
	const TAG_IMPRESSAO = '[impressao]';
	const TAG_NEGRITO_ABRIR = '[negrito]';
	const TAG_NEGRITO_FECHAR = '[/negrito]';
	const TAG_SCANNER = '[scanner]';
	const TAG_VER_MAIS = '[ver_mais]';
	const TAG_PEB = '[p&b]';
	const TAG_SERVIDOR_DOCUMENTOS = '[servidor_documentos]';
	const TAG_FAX_OPCIONAL = '[fax_opcional]';
	
	const CODIGO_COPIA = '<span class="icone-descricao icone-copia">Cópia</span>';
	const CODIGO_CORES = '<span class="icone-descricao icone-cores">Cores</span>';
	const CODIGO_FAX = '<span class="icone-descricao icone-fax">Fax</span>';
	const CODIGO_IMPRESSAO = '<span class="icone-descricao icone-impressao">Impressão</span>';
	const CODIGO_NEGRITO_ABRIR = '<strong>';
	const CODIGO_NEGRITO_FECHAR = '</strong>';
	const CODIGO_SCANNER = '<span class="icone-descricao icone-scanner">Scanner</span>';
	const CODIGO_PEB = '<span class="icone-descricao icone-peb">P&B</span>';
	const CODIGO_SERVIDOR_DOCUMENTOS = '<span class="icone-descricao icone-servidor-documentos">Servidor de Documentos</span>';
	const CODIGO_FAX_OPCIONAL = '<span class="icone-descricao icone-fax-opcional">Fax opcional</span>';
	
	public static function dc_product_2_oc_product($dc_product, $product_config) {
		// extrai as variaveis do array de config
		extract($product_config);
		
		$oc_product['dc_id'] = $dc_product['CODIGO'];
		$oc_product['name'] = $dc_product['NOME'];
		$oc_product['description'] = nl2br(self::_replace_product_tags($dc_product['DESCRICAO']));
		$oc_product['dc_obs'] = nl2br(self::_replace_product_tags($dc_product['DESCRICAO_INTERNA']));
		$oc_product['status'] = $dc_product['STATUS'] == 'A' && $dc_product['EXIBIR_WEB'] == 'S';
		$oc_product['ean'] = $dc_product['EAN'];
		$oc_product['mpn'] = $dc_product['REFERENCIA'];
		$oc_product['price'] = 9999999;
		$oc_product['quantity'] = 0;
		$oc_product['special'] = ''; // TODO (float) $dc_product->precoPromocional;
		$oc_product['subtract'] = $product_config['subtract'];
		$oc_product['shipping'] = $product_config['shipping'];
		$oc_product['stock_status_id'] = $product_config['stock_status_id'];
		$oc_product['length'] = (float) $dc_product['COMPRIMENTO'];
		$oc_product['height'] = (float) $dc_product['ALTURA'];
		$oc_product['width'] = (float) $dc_product['LARGURA'];
		$oc_product['length_class_id'] = $product_config['length_class_id'];
		
		$oc_product['weight'] = $dc_product[$weight_field];
		$oc_product['weight_class_id'] = $product_config['weight_class_id'];
		$oc_product['date_available'] = date('Y-m-d', strtotime('1 day ago'));
		
		$field_cat = $category_fields['cat'];
		$field_subCat = $category_fields['subCat'];
		if (isset($dc_product['ID_' . $field_cat], $dc_product['NOME_' . $field_cat])) {
			$oc_product['category']['id'] = $dc_product['ID_' . $field_cat];
			$oc_product['category']['name'] = $dc_product['NOME_' . $field_cat];
		}
		
		if (isset($dc_product['ID_' . $field_subCat], $dc_product['NOME_' . $field_subCat])) {
			$oc_product['sub_category']['id'] = $dc_product['ID_' . $field_subCat];
			$oc_product['sub_category']['name'] = $dc_product['NOME_' . $field_subCat];
		}
		
		if (isset($dc_product['ID_' . $manufacturer_field], $dc_product['NOME_' . $manufacturer_field])) {
			$oc_product['manufacturer']['id'] = $dc_product['ID_' . $manufacturer_field];
			$oc_product['manufacturer']['name'] = $dc_product['NOME_' . $manufacturer_field];
		}
		
		$empty_fields = array(
			'model', 'sku', 'upc', 'jan', 'isbn', 'location', 
			'minimum', 'points', 'sort_order', 'tax_class_id'
		);
		foreach ($empty_fields as $f) {
			$oc_product[$f] = '';
		}
		
		return $oc_product;
	}
	
	private static function _replace_product_tags($descricao) {
		$descricao = str_replace(self::TAG_COPIA, self::CODIGO_COPIA, $descricao);
		$descricao = str_replace(self::TAG_CORES, self::CODIGO_CORES, $descricao);
		$descricao = str_replace(self::TAG_FAX, self::CODIGO_FAX, $descricao);
		$descricao = str_replace(self::TAG_IMPRESSAO, self::CODIGO_IMPRESSAO, $descricao);
		$descricao = str_replace(self::TAG_NEGRITO_ABRIR, self::CODIGO_NEGRITO_ABRIR, $descricao);
		$descricao = str_replace(self::TAG_NEGRITO_FECHAR, self::CODIGO_NEGRITO_FECHAR, $descricao);
		$descricao = str_replace(self::TAG_SCANNER, self::CODIGO_SCANNER, $descricao);
		$descricao = str_replace(self::TAG_PEB, self::CODIGO_PEB, $descricao);
		$descricao = str_replace(self::TAG_SERVIDOR_DOCUMENTOS, self::CODIGO_SERVIDOR_DOCUMENTOS, $descricao);
		$descricao = str_replace(self::TAG_FAX_OPCIONAL, self::CODIGO_FAX_OPCIONAL, $descricao);
			
		return $descricao;
	} 
	
	public static function dc_simple_product_2_oc_product($dc_product, $product_config) {
		$oc_product['dc_id'] = $dc_product['CODIGO'];
		$oc_product['special'] = '';// TODO (float) $dc_product->precoPromocional;
		
		return $oc_product;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 11 de jan de 2019
	 * @param unknown $oc_customer
	 * @param unknown $dc_config
	 */
	public static function oc_customer_2_dc_customer($oc_customer, $dc_config) {
		$dc_customer = array();
		
		$dc_customer['NOME'] = $oc_customer['cpf'] ? ($oc_customer['firstname'] . ' ' . $oc_customer['lastname']) : $oc_customer['razao_social'];
		$dc_customer['NOME'] = mb_strtoupper($dc_customer['NOME']);
		
		$dc_customer['PESSOA'] = $oc_customer['cpf'] ? 'F' : 'J';
		$dc_customer['CNPJ'] = preg_replace("/[^0-9]/", '', $oc_customer['cnpj']);
		$dc_customer['CPF'] = preg_replace("/[^0-9]/", '', $oc_customer['cpf']);
		$dc_customer['INSCEST'] = $oc_customer['inscricao_estadual'];
		
		$dc_customer['DTNASC'] = $oc_customer['data_nascimento'];
		if (strpos($oc_customer['data_nascimento'], '/') !== false) {
			$dc_customer['DTNASC'] = self::view_date_2_db_date($oc_customer['data_nascimento']);
		}
		
		$dc_customer['DTCAD'] = date('Y-m-d H:i:s');
		$dc_customer['SITUACAO'] = self::SITUACAO_ATIVO;
		$dc_customer['EMPRESA'] = $dc_config['dc_default_customer_company'];
		$dc_customer['OPCAD'] = $dc_config['dc_order_seller_name'];
		$dc_customer['VENDEDOR'] = $dc_config['dc_order_seller_code'];
		$dc_customer['NIVEL'] = $dc_config['dc_default_customer_level'];
		$dc_customer['CONCEITO'] = $dc_config['dc_default_customer_group'];
		$dc_customer['CODCEN'] = $dc_config['dc_default_center_code'];
		$dc_customer['CODSUB'] = $dc_config['dc_default_subcenter_code'];
		$dc_customer['PLANCON'] = (string) $dc_config['dc_default_billing_plan'];
		
		$dc_customer['OBS'] = 'Cliente cadastrado através da loja virtual.';
		$dc_customer['FANTASIA'] = '';
		$dc_customer['IDENT'] = '';
		$dc_customer['INSCMUN'] = '';
		
		return $dc_customer;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 11 de jan de 2019
	 * @param unknown $oc_address
	 * @param unknown $dc_config
	 */
	public static function oc_address_2_dc_address($oc_address, $dc_config, $list_zones) {
		$dc_address = array();
	
		$dc_address['DTCAD'] = date('Y-m-d H:i:s');
		$dc_address['OPCAD'] = $dc_config['dc_order_seller_name'];
		$dc_address['BAIRRO'] = $oc_address['address_2'];
		$dc_address['CEP'] = $oc_address['postcode'];
		$dc_address['CIDADE'] = $oc_address['city'];
		$dc_address['COMP'] = $oc_address['complemento'];
		$dc_address['EMAIL'] = $oc_address['email'];
		$dc_address['END'] = $oc_address['address_1'];
		$dc_address['ESTADO'] = isset($list_zones[$oc_address['zone_id']]) ? $list_zones[$oc_address['zone_id']] : '';
		$dc_address['FONE'] = preg_replace("/[^0-9]/", '', $oc_address['telephone']);
		$dc_address['CELULAR'] = preg_replace("/[^0-9]/", '', $oc_address['fax']);
		$dc_address['NUM'] = $oc_address['numero'];
		$dc_address['PAIS'] = 'BRASIL';
		
		$dc_address['FONEAUX'] = '';
	
		return $dc_address;
	}
	
	/**
	 * 
	 * @param unknown $dc_customer
	 * @param unknown $dc_config
	 * @param unknown $list_zones
	 * @return multitype:string number mixed NULL unknown
	 */
	public static function dc_customer_2_oc_customer($dc_customer, $dc_config, $config, $list_zones) {
		$oc_customer = array();
		
		$dc_address = $dc_customer['address'];
		$dc_customer = $dc_customer['customer'];
	
		$names = explode(' ', $dc_customer['NOME']);
		$oc_customer['firstname'] = array_shift($names);
		$oc_customer['lastname'] = '';
		if ($names) {
			$oc_customer['lastname'] = implode(' ', $names);
		}
		
		$oc_customer['doing_import'] = 1;
		$oc_customer['dc_id'] = $dc_customer['CODIGO'];
		$oc_customer['cpf'] = '';
		$oc_customer['cnpj'] = '';
		$oc_customer['company'] = '';
		$oc_customer['company_id'] = '';
		$oc_customer['tax_id'] = '';
		$oc_customer['razao_social'] = '';
		$oc_customer['sexo'] = '';
		$oc_customer['rg'] = '';
		$oc_customer['apelido'] = '';
		if ($dc_customer['CPF']) {
			$oc_customer['cpf'] = preg_replace("/[^0-9]/", '', $dc_customer['CPF']);
			$oc_customer['password'] = $oc_customer['cpf'];
		} else if ($dc_customer['CNPJ']) {
			$oc_customer['cnpj'] = preg_replace("/[^0-9]/", '', $dc_customer['CNPJ']);
			$oc_customer['razao_social'] = $dc_customer['NOME'];
			$oc_customer['password'] = $oc_customer['cnpj'];
		}
		
		$oc_customer['data_nascimento'] = date('d/m/Y', strtotime($dc_customer['DTNASC']));
		$oc_customer['inscricao_estadual'] = $dc_customer['INSCEST'];
		$oc_customer['customer_group_id'] = $dc_customer['CONCEITO'];
		$oc_customer['email'] = $dc_address['EMAIL'];
		$oc_customer['telephone'] = preg_replace("/[^0-9]/", '', $dc_address['FONE']);
		$oc_customer['fax'] = preg_replace("/[^0-9]/", '', $dc_address['CELULAR']);
		$oc_customer['newsletter'] = 1;
		
		$oc_customer['address_1'] = $dc_address['END'];
		$oc_customer['address_2'] = $dc_address['BAIRRO'];
		$oc_customer['postcode'] = $dc_address['CEP'];
		$oc_customer['city'] = $dc_address['CIDADE'];
		$oc_customer['complemento'] = $dc_address['COMP'];
		$oc_customer['numero'] = $dc_address['NUM'];
		
		$oc_customer['zone_id'] = $config->get('config_zone_id');
		foreach ($list_zones as $zone_id => $code) {
			if ($code == $dc_address['ESTADO']) {
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
	public static function oc_order_2_dc_order($oc_order, $oc_products, $company, $operation_code, $shipping, $discount, $customer, $dc_config) {
		$dc_order = array();
		
		$dc_order['CODCLI'] = $customer['dc_id'];
		$dc_order['CODEMP'] = $company['company_id'];
		$dc_order['OBSADD'] = $oc_order['comment'];
		if ($company['zone_code'] == $customer['uf']) {
			$dc_order['NATUREZA'] = $company['order_type_zone_in'];
		} else {
			$dc_order['NATUREZA'] = $company['order_type_zone_out'];
		}
		
		$dc_order['STATUS'] = $dc_config['dc_order_status_new'];
		$dc_order['OPCAD'] = $dc_config['dc_order_seller_name'];
		$dc_order['VEND'] = $dc_config['dc_order_seller_code'];
		$dc_order['TIPODESC'] = $operation_code;
		$dc_order['CONDPAG'] = $oc_order['payment_condition'];
		$dc_order['CODTEC'] = $dc_config['dc_order_codtec'];
		$dc_order['OPERACAO'] = $dc_config['dc_order_operation_number'];
		$dc_order['PREST'] = $dc_config['dc_order_prest_serv'];
		
		$dc_order['VLRDESCONTO'] = $discount;
		$dc_order['VLRFRETE'] = $shipping;
		$dc_order['VLRFRETE2'] = $shipping;
		
		$dc_order['DTCAD'] = date('Y-m-d H:i:s');
		$dc_order['DATA'] = date('Y-m-d H:i:s');
		
		$obs = 'Pedido cadastrado pela loja virtual (Web).';
		$obs .= ' Frete Escolhido: ' . $oc_order['shipping_method'];
		$obs .= ' Valor do Frete: ' . $shipping;
		$dc_order['OBS'] = $obs;
		
		$dc_itens = array();
		$total_shipping_itens = 0;
		foreach ($oc_products as $index => $product) {
			$dc_item = array();
			$price_info = $product['dc_product_price'];
			$price_unit = $product['is_special'] ? $product['price'] : $price_info['VALOR'];
			
			$product_total = round($price_unit * $product['quantity']);
			$taxes = self::_calculate_product_taxes($product_total, $price_info);
			
			$dc_item['CODEMP'] = $company['company_id'];
			$dc_item['OPCAD'] = $dc_config['dc_order_seller_name'];
			$dc_item['NATUREZA'] = $dc_order['NATUREZA'];
			$dc_item['TABELA'] = $customer['customer_group_id'];
			$dc_item['PRODUTO'] = $product['dc_id'];
			$dc_item['PRUNIT'] = $price_unit;
			$dc_item['VLRDESC'] = $product['discount'];
			$dc_item['QTPROD'] = $product['quantity'];
			$dc_item['PRBACKUP'] = $price_unit;
			$dc_item['TOTVALOR'] = $product_total;
			
			$dc_item['CUSTO'] = $product['custo'];
			$dc_item['CUSTOCOMPRA'] = $product['custo_compra'];
			$dc_item['TIPODESC'] = $dc_order['TIPODESC'];
			
			$dc_item['ICMS'] = $price_info['ICMS'];
			$dc_item['PERCIPI'] = $price_info['IPI'];
			$dc_item['BASERED'] = $price_info['BASERED'];
			$dc_item['VLRIPI'] = $price_info['VALORIPI'];
			$dc_item['VLRST'] = $price_info['VALORST'];
			$dc_item['COMISSAO'] = $price_info['COMISSAO'];
			
			$dc_item['BASEST'] = $taxes['base_st'];
			$dc_item['VLRICMS'] = $taxes['vlr_icms'];
			$dc_item['PERCST'] = $taxes['perc_st'];
			$dc_item['BASEICMS'] = $taxes['base_icms'];
			
			$is_last = ($index == (count($oc_products) - 1));
			$item_shipping = self::get_item_shipping($is_last, $oc_order['total'], $dc_item['TOTVALOR'], $shipping, $total_shipping_itens);
			$total_shipping_itens += $item_shipping;
			
			$dc_item['VLRFRETE'] = $item_shipping;
			$dc_item['VLRFRETEB'] = $item_shipping;
			$dc_item['DTCAD'] = date('Y-m-d H:i:s');
			
			$dc_itens[] = $dc_item;
		}
		
		return array('order' => $dc_order, 'itens' => $dc_itens);
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
	 * @param unknown $dc_company
	 */
	public static function dc_company_2_oc_company($dc_data) {
		$oc_data = array();
		$oc_data['company_id'] 	= $dc_data['CODIGO'];
		$oc_data['name'] 		= $dc_data['NOME'];
		
		return $oc_data;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 11 de dez de 2018
	 * @param unknown $dc_customer_group
	 */
	public static function dc_customer_group_2_oc_customer_group($dc_data) {
		$oc_data = array();
		$oc_data['customer_group_id'] 	= $dc_data['CODIGO'];
		$oc_data['name'] 				= $dc_data['NOME'];
	
		return $oc_data;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 11 de dez de 2018
	 * @param unknown $dc_payment_condition
	 */
	public static function dc_payment_condition_2_oc_payment_condition($dc_data) {
		$oc_data = array();
		$oc_data['payment_id'] 	= $dc_data['CODIGO'];
		$oc_data['name'] 		= $dc_data['NOME'];
		
		return $oc_data;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 14 de jun de 2019
	 * @param unknown $dc_data
	 */
	public static function dc_order_status_2_oc_order_status($dc_data) {
		$oc_data = array();
		$oc_data['status_id'] 	= $dc_data['CODIGO'];
		$oc_data['name'] 		= utf8_encode($dc_data['NOME']);
	
		return $oc_data;
	}
	
	public static function oc_datetime_2_dc_datetime($datetime) {
		list($date, $time) = explode(' ', $datetime);
		list($year, $month, $day) = explode('-', $date);
		
		return array(
			'date' => $day . '/' . $month . '/' . $year,
			'time' => $time
		);
	}
	
	public static function dc_date_2_oc_date($date) {
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