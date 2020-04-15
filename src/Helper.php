<?php

namespace TagplusBnw;

class Helper {
	const STATUS_ATIVO = 1;
	const STATUS_INATIVO = 0;
	
	const ORDER_STATUS_NEW = 'A';
	const PERSON_TYPE_F = 'F';
	const PERSON_TYPE_J = 'J';
	
	const PRODUCT_TYPE_NORMAL = 'N';
	const PRODUCT_TYPE_GRADE = 'G';
	
	public static function tgp_product_2_oc_product($tgp_product, $product_config) {
		// extrai as variaveis do array de config
		extract($product_config);
		
		$oc_product['tgp_id'] = $tgp_product->id;
		$oc_product['name'] = $tgp_product->descricao;
		$oc_product['status'] = $tgp_product->ativo;
		$oc_product['sku'] = $tgp_product->codigo;
		$oc_product['ean'] = $tgp_product->codigo_barras;
		$oc_product['price'] = $tgp_product->valor_venda_varejo;
		$oc_product['quantity'] = $tgp_product->qtd_revenda;
		$oc_product['special'] = '';
		$oc_product['subtract'] = $product_config['subtract'];
		$oc_product['shipping'] = $product_config['shipping'];
		$oc_product['stock_status_id'] = $product_config['stock_status_id'];
		$oc_product['length'] = (float) $tgp_product->comprimento;
		$oc_product['height'] = (float) $tgp_product->altura;
		$oc_product['width'] = (float) $tgp_product->largura;
		$oc_product['length_class_id'] = $product_config['length_class_id'];
		
		$oc_product['weight'] = $tgp_product->peso;
		$oc_product['weight_class_id'] = $product_config['weight_class_id'];
		$oc_product['date_available'] = date('Y-m-d', strtotime('1 day ago'));
		
		// TODO imagens
		
		if (isset($tgp_product->categoria)) {
			$oc_product['category']['id'] = $tgp_product->categoria;
		}
		
		if (isset($tgp_product->fornecedores) && !empty($tgp_product->fornecedores)) {
			foreach ($tgp_product->fornecedores as $item) {
				if ($item->fabricante) {
					$oc_product['manufacturer']['id'] = $item->id;
					$oc_product['manufacturer']['name'] = $item->nome_fantasia ? $item->nome_fantasia : $item->razao_social;
				}
			}
		}
		
		if (isset($tgp_product->atributos) && !empty($tgp_product->atributos)) {
			foreach ($tgp_product->atributos as $item) {
				$oc_product['attributes'] = array();
				// TODO attributes
				/*foreach ($tgp_product->atributos as $item) {
					$option = explode(' ', $item['descricao']);
					$option_name = array_shift($option);
					$option_value = implode(' ', $option);
				
					$oc_product['options'][] = array(
						'id' => $item['id'],
						'sku' => $item['sku'],
						'option_name' => $option_name,
						'option_value' => $option_value,
						'quantity' => $item['qtn_revenda'],
					);
				}*/
			}
		}
		
		if ($tgp_product->tipo == self::PRODUCT_TYPE_GRADE && isset($tgp_product->filhos) && !empty($tgp_product->filhos)) {
			$oc_product['options'] = array();
			foreach ($tgp_product->filhos as $item) {
				$option = explode(' ', $item['descricao']);
				$option_name = array_shift($option);
				$option_value = implode(' ', $option);
				
				$oc_product['options'][] = array(
					'id' => $item['id'],
					'sku' => $item['sku'],
					'option_name' => $option_name,
					'option_value' => $option_value,
					'quantity' => $item['qtn_revenda'],
				);
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
		$oc_product['tgp_id'] = $tgp_product->id;
		$oc_product['price'] = $tgp_product->valor_venda_varejo;
		$oc_product['quantity'] = $tgp_product->qtd_revenda;
		$oc_product['status'] = $tgp_product->ativo;
		
		return $oc_product;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 11 de jan de 2019
	 * @param unknown $oc_customer
	 * @param unknown $tgp_config
	 */
	public static function oc_customer_2_tgp_customer($oc_customer, $oc_customer_addresses, $tgp_config) {
		$tgp_customer = array();
		
		$tgp_customer['codigo_externo'] = (string) $oc_customer['customer_id'];
		$tgp_customer['ativo'] = self::STATUS_ATIVO;
		
		if ($oc_customer['cpf']) {
			$tgp_customer['tipo'] = self::PERSON_TYPE_F;
			$tgp_customer['sexo'] = strtoupper($oc_customer['sexo']);
			$tgp_customer['cpf'] = preg_replace("/[^0-9]/", '', $oc_customer['cpf']);
			$tgp_customer['razao_social'] = $oc_customer['firstname'] . ' ' . $oc_customer['lastname'];
			$tgp_customer['data_nascimento'] = $oc_customer['data_nascimento'];
			if (strpos($oc_customer['data_nascimento'], '/') !== false) {
				$tgp_customer['data_nascimento'] = self::view_date_2_db_date($oc_customer['data_nascimento']);
			}
		} else {
			$tgp_customer['tipo'] = self::PERSON_TYPE_J;
			$tgp_customer['cnpj'] = preg_replace("/[^0-9]/", '', $oc_customer['cnpj']);
			$tgp_customer['razao_social'] = $oc_customer['razao_social'];
			$tgp_customer['ie'] = $oc_customer['inscricao_estadual'];
			$tgp_customer['responsavel'] = $oc_customer['firstname'] . ' ' . $oc_customer['lastname'];
		}
		
		$tgp_customer['informacao_adicional'] = 'Cliente cadastrado através da loja virtual.';
		$tgp_customer['contatos'] = array();
		$tgp_customer['contatos'][] = array(
			'descricao' => $oc_customer['email'],
			'tipo_contato' => $tgp_config['tgp_contact_email'],
			'tipo_cadastro' => $tgp_config['tgp_register_customer_type'],
		);
		
		if ($oc_customer['telephone']) {
			$tgp_customer['contatos'][] = array(
				'descricao' => $oc_customer['telephone'],
				'tipo_contato' => $tgp_config['tgp_contact_phone'],
				'tipo_cadastro' => $tgp_config['tgp_register_customer_type'],
			);
		}
		
		if ($oc_customer['fax']) {
			$tgp_customer['contatos'][] = array(
				'descricao' => $oc_customer['fax'],
				'tipo_contato' => $tgp_config['tgp_contact_mobile'],
				'tipo_cadastro' => $tgp_config['tgp_register_customer_type'],
			);
		}
		
		$tgp_customer['enderecos'] = array();
		foreach ($oc_customer_addresses as $item) {
			$tgp_customer['enderecos'][] = array(
				'principal' => $oc_customer['address_id'] == $item['address_id'],
				'cep' => $item['postcode'],
				'logradouro' => $item['address_1'],
				'numero' => $item['numero'],
				'complemento' => $item['complemento'],
				'bairro' => $item['address_2'],
				'pais' => $tgp_config['tgp_default_country'],
				'tipo_cadastro' => $tgp_config['tgp_register_address_type'],
			);
		}
				
		return $tgp_customer;
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
		
		$empty_fields = array(
			'cpf', 'cnpj', 'company', 'company_id', 'tax_id', 'razao_social', 'sexo', 'rg', 'apelido',
			'email', 'telephone', 'fax',
		);
		foreach ($empty_fields as $item) {
			$oc_customer[$item] = '';
		}
		
		$oc_customer['doing_import'] = 1;
		$oc_customer['newsletter'] = 1;
		$oc_customer['tgp_id'] = (string) $tgp_customer['codigo_externo'];
		$oc_customer['status'] = self::STATUS_ATIVO;
		
		$names = explode(' ', $tgp_customer['razao_social']);
		if ($tgp_customer['tipo'] == self::PERSON_TYPE_F) {
			$oc_customer['rg'] = $tgp_customer['rg'];
			$oc_customer['sexo'] = strtolower($tgp_customer['sexo']);
			$oc_customer['cpf'] = preg_replace("/[^0-9]/", '', $tgp_customer['cpf']); 
			
			$oc_customer['data_nascimento'] = $tgp_customer['data_nascimento'];
			if (strpos($tgp_customer['data_nascimento'], '/') !== false) {
				$oc_customer['data_nascimento'] = self::view_date_2_db_date($tgp_customer['data_nascimento']);
			}
		} else {
			$oc_customer['cnpj'] = preg_replace("/[^0-9]/", '', $tgp_customer['cnpj']);
			$oc_customer['razao_social'] = $tgp_customer['razao_social'];
			$oc_customer['inscricao_estadual'] = $tgp_customer['ie'];
			if ($tgp_customer['responsavel'] && strlen($tgp_customer['responsavel']) > 3) {
				$names = explode(' ', $tgp_customer['responsavel']);
			}
		}
		
		$oc_customer['firstname'] = array_shift($names);
		$oc_customer['lastname'] = implode(' ', $names);
		$oc_customer['date_added'] = date('Y-m-d H:i:s');
		$oc_customer['customer_group_id'] = $config->get('config_customer_group_id');
		if (is_array($tgp_customer['contatos'])) {
			foreach ($tgp_customer['contatos'] as $item) {
				if ($item['tipo_contato'] == $tgp_config['tgp_contact_email']) {
					$oc_customer['email'] = trim($item['descricao']);
				} else if ($item['tipo_contato'] == $tgp_config['tgp_contact_phone']) {
					$oc_customer['telephone'] = trim($item['descricao']);
				} else if ($item['tipo_contato'] == $tgp_config['tgp_contact_mobile']) {
					$oc_customer['fax'] = trim($item['descricao']);
				}
			}
		}
		
		if (is_array($tgp_customer['enderecos'])) {
			foreach ($tgp_customer_addresses as $item) {
				if ($item['principal']) {
					$oc_customer['postcode'] = $item['cep'];
					$oc_customer['address_1'] = $item['logradouro'];
					$oc_customer['numero'] = $item['numero'];
					$oc_customer['complemento'] = $item['complemento'];
					$oc_customer['address_2'] = $item['bairro'];
					$oc_customer['country_id'] = $config->get('config_country_id');
					// TODO zone_id $oc_customer['zone_id'] = $config->get('config_zone_id');
				}
			}
		}
				
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
	public static function oc_order_2_tgp_order($oc_order, $order_totals, $oc_products, $customer, $tgp_config) {
		$tgp_order = array();
		
		$total_info = self::_get_shipping_and_discount($oc_order['total'], $order_totals, $oc_products);
		$oc_products = $total_info['products'];
		
		$tgp_order['codigo_externo'] = (string) $oc_order['order_id'];
		$tgp_order['status'] = $order['is_paid'] ? $tgp_config['tgp_order_status_paid'] : self::ORDER_STATUS_NEW;
		$tgp_order['vendedor'] = $tgp_config['tgp_order_seller_code'];
		$tgp_order['cliente'] = $customer['tgp_id'];
		$tgp_order['valor_desconto'] = $total_info['total_discount'];
		$tgp_order['valor_frete'] = $total_info['shipping'];
		$tgp_order['observacoes'] = $oc_order['comment'] . '<br />Pedido cadastrado pela loja virtual.';
		
		$tgp_order['itens'] = array();
		foreach ($oc_products as $index => $product) {
			$tgp_order['itens'][] = array(
				'item' 				=> $index,
				'produto_servico' 	=> $product['tgp_id'],
				'qtd' 				=> $product['quantity'],
				'valor_unitario' 	=> $product['price'],
				'valor_desconto' 	=> $product['discount'],
			);
		}
		
		return $tgp_order;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 15 de abr de 2020
	 * @param unknown $order_total
	 * @param unknown $totals
	 * @param unknown $products
	 */
	private static function _get_shipping_and_discount($order_total, $totals, $products) {
		// obtem frete e desconto
		$shipping = 0;
		foreach ($totals as $total) {
			if ($total['code'] == 'shipping') {
				$shipping = $total['value'];
			}
		}
	
		$total_products_no_discount = self::_get_total_products($products);
		$total_discount = self::_get_total_discount($totals);
		$products = self::_calculate_product_discount($total_discount, $total_products_no_discount, $products);
		$total_products_with_discount = 0;
		foreach ($products as $p) {
			$total_products_with_discount += ($p['total'] - $p['discount']);
		}
	
		// valida se (produtos + frete) - desconto = total pedido
		$products = $this->_adjust_discount($order_total, $shipping, $total_products_with_discount, $products);
	
		return compact('shipping', 'products', 'total_discount');
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 15 de abr de 2020
	 * @param unknown $products
	 */
	private static function _get_total_products($products) {
		$products_total = 0;
		foreach ($products as $p) {
			$products_total += $p['total'];
		}
	
		return $products_total;
	}
	
	/**
	 * 
	 * @author Rande A. Moreira
	 * @since 15 de abr de 2020
	 * @param unknown $totals
	 */
	private static function _get_total_discount($totals) {
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
	 * @since 15 de abr de 2020
	 * @param unknown $total_discount
	 * @param unknown $total_products_no_discount
	 * @param unknown $products
	 */
	private static function _calculate_product_discount($total_discount, $total_products_no_discount, $products) {
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
	 * @since 15 de abr de 2020
	 * @param unknown $order_total
	 * @param unknown $shipping_value
	 * @param unknown $products_total_with_discount
	 * @param unknown $products
	 */
	private static function _adjust_discount($order_total, $shipping_value, $products_total_with_discount, $products) {
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
}
?>