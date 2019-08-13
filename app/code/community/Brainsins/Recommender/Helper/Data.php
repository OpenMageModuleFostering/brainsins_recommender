<?php
/**
 * BrainSINS' Magento Extension allows to integrate the BrainSINS
 * personalized product recommendations into a Magento Store.
 * Copyright (c) 2014 Social Gaming Platform S.R.L.
 *
 * This file is part of BrainSINS' Magento Extension.
 *
 *  BrainSINS' Magento Extension is free software: you can redistribute it
 *  and/or modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  Foobar is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Please do not hesitate to contact us at info@brainsins.com
 */

/**
 * ID BRAINSINS PAGE
 * Home - 1
 * Producto - 2
 * Carrito - 3
 * Thank you - 4
 * E-mail - 5
 * Abandono de carrito - 6
 * Categoría - 7
 */

class Brainsins_Recommender_Helper_Data extends Mage_Core_Helper_Abstract
{
	protected static $_api_url = 'http://durotar-api.brainsins.com/RecSinsAPI/api/';
	protected static $_pages = 'home,product,category,cart,checkout'; //-->Not needed yet

	public static function getApiUrl() {
		$apiMode = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_advanced/api_mode', Mage::app()->getStore()->getStoreId());
		if ($apiMode == "1") {
			return 'http://api.brainsins.com/RecSinsAPI/api/';
		} else {
			return 'http://durotar-api.brainsins.com/RecSinsAPI/api/';
		}

	}

	public function getRecommenders($page)
	{
		$recommenders = array();
		$extension_enabled = Mage::getStoreConfigFlag('brainsins_recommender_options/brainsins_recommender_general/enabled', Mage::app()->getStore()->getStoreId());
		$bskey = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_general/bs_key', Mage::app()->getStore()->getStoreId());
		$msg_not_available = Mage::helper('brainsins_recommender')->__('No available recommenders in this page');

		if(!$extension_enabled || $bskey == '')
			return array('value' => '', 'label' => $msg_not_available);

		$response = @file_get_contents(self::getApiUrl()."recommender/retrieve.xml?token=".$bskey);

		if ($response !== false)
		{
			$xmlData = simplexml_load_string($response);
			$jsonData = json_decode(json_encode((array) $xmlData), true);
			$recommenders[] = array('value' => '', 'label' => Mage::helper('brainsins_recommender')->__('--- Recommender name ---'));
			foreach ($jsonData['recommenders']['recommender'] as $recommender)
			{
				if($recommender['page'] == $page)
					$recommenders[] = array('value' => $recommender['id_recommender'], 'label' => $recommender['name']);
			}
			return $recommenders;
		}
		else
			$recommenders[0] = array('value' => '', 'label' => $msg_not_available);

		return $recommenders;
	}

	public function getPositions($page)
	{
		$positions = array();
		$positions[] = array('value' => '', 'label' => Mage::helper('brainsins_recommender')->__('--- Recommender position ---'));

		switch ($page) {
			case '1':
				$positions[] = array('value' => 'after_body_start', 'label' => Mage::helper('brainsins_recommender')->__('Top'));
				$positions[] = array('value' => 'header', 'label' => Mage::helper('brainsins_recommender')->__('Header'));
				$positions[] = array('value' => 'content', 'label' => Mage::helper('brainsins_recommender')->__('Main content'));
				$positions[] = array('value' => 'right', 'label' => Mage::helper('brainsins_recommender')->__('Right column'));
				$positions[] = array('value' => 'left', 'label' => Mage::helper('brainsins_recommender')->__('Left column'));
				$positions[] = array('value' => 'footer', 'label' => Mage::helper('brainsins_recommender')->__('Footer'));
				$positions[] = array('value' => 'before_body_end', 'label' => Mage::helper('brainsins_recommender')->__('Bottom'));
				$positions[] = array('value' => 'custom', 'label' => Mage::helper('brainsins_recommender')->__('Custom position'));
				$positions[] = array('value' => '-', 'label' => Mage::helper('brainsins_recommender')->__('--- Other positions ---'));
				$positions[] = array('value' => 'catalog.topnav', 'label' => Mage::helper('brainsins_recommender')->__('Navigation bar'));
				$positions[] = array('value' => 'breadcrumbs', 'label' => Mage::helper('brainsins_recommender')->__('Breadcrumb'));
				$positions[] = array('value' => 'cart_sidebar', 'label' => Mage::helper('brainsins_recommender')->__('Cart sidebar'));
				$positions[] = array('value' => 'catalog.compare.sidebar', 'label' => Mage::helper('brainsins_recommender')->__('Product compare sidebar'));
				$positions[] = array('value' => 'left.reports.product.viewed', 'label' => Mage::helper('brainsins_recommender')->__('Left column recently viewed'));
				$positions[] = array('value' => 'right.reports.product.viewed', 'label' => Mage::helper('brainsins_recommender')->__('Right column recently viewed'));
				break;
			case '2':
				$positions[] = array('value' => 'product.info', 'label' => Mage::helper('brainsins_recommender')->__('Main content'));
				$positions[] = array('value' => 'right', 'label' => Mage::helper('brainsins_recommender')->__('Right column'));
				$positions[] = array('value' => 'left', 'label' => Mage::helper('brainsins_recommender')->__('Left column'));
				$positions[] = array('value' => 'description', 'label' => Mage::helper('brainsins_recommender')->__('Product description'));
				$positions[] = array('value' => 'additional', 'label' => Mage::helper('brainsins_recommender')->__('Product attributes'));
				$positions[] = array('value' => 'product_options', 'label' => Mage::helper('brainsins_recommender')->__('Product options'));
				$positions[] = array('value' => 'prices', 'label' => Mage::helper('brainsins_recommender')->__('Product prices'));
				$positions[] = array('value' => 'product_additional_data', 'label' => Mage::helper('brainsins_recommender')->__('Product additional information'));
				$positions[] = array('value' => 'upsell_products', 'label' => Mage::helper('brainsins_recommender')->__('Upsell products'));
				$positions[] = array('value' => 'custom', 'label' => Mage::helper('brainsins_recommender')->__('Custom position'));
				$positions[] = array('value' => '-', 'label' => Mage::helper('brainsins_recommender')->__('------------- Other positions -------------'));
				$positions[] = array('value' => 'catalog.product.related', 'label' => Mage::helper('brainsins_recommender')->__('Related products'));
				$positions[] = array('value' => 'media', 'label' => Mage::helper('brainsins_recommender')->__('Product media gallery'));
				$positions[] = array('value' => 'product_tag_list', 'label' => Mage::helper('brainsins_recommender')->__('Product tag list'));
				$positions[] = array('value' => 'extrahint', 'label' => Mage::helper('brainsins_recommender')->__('Product extra hint'));
				$positions[] = array('value' => 'product.info.addtocart', 'label' => Mage::helper('brainsins_recommender')->__('Product add to cart'));
				$positions[] = array('value' => 'product.info.addto', 'label' => Mage::helper('brainsins_recommender')->__('Product other "add to" options'));
				$positions[] = array('value' => 'cart_sidebar', 'label' => Mage::helper('brainsins_recommender')->__('Cart sidebar'));
				$positions[] = array('value' => 'catalog.compare.sidebar', 'label' => Mage::helper('brainsins_recommender')->__('Product compare sidebar'));
				$positions[] = array('value' => 'left.reports.product.viewed', 'label' => Mage::helper('brainsins_recommender')->__('Left column recently viewed'));
				$positions[] = array('value' => 'right.reports.product.viewed', 'label' => Mage::helper('brainsins_recommender')->__('Right column recently viewed'));
				$positions[] = array('value' => 'after_body_start', 'label' => Mage::helper('brainsins_recommender')->__('Top'));
				$positions[] = array('value' => 'header', 'label' => Mage::helper('brainsins_recommender')->__('Header'));
				$positions[] = array('value' => 'catalog.topnav', 'label' => Mage::helper('brainsins_recommender')->__('Navigation bar'));
				$positions[] = array('value' => 'breadcrumbs', 'label' => Mage::helper('brainsins_recommender')->__('Breadcrumb'));
				$positions[] = array('value' => 'footer', 'label' => Mage::helper('brainsins_recommender')->__('Footer'));
				$positions[] = array('value' => 'before_body_end', 'label' => Mage::helper('brainsins_recommender')->__('Bottom'));
				break;
			case '3':
				$positions[] = array('value' => 'content', 'label' => Mage::helper('brainsins_recommender')->__('Main content'));
				$positions[] = array('value' => 'crosssell', 'label' => Mage::helper('brainsins_recommender')->__('Cross sell area'));
				$positions[] = array('value' => 'totals', 'label' => Mage::helper('brainsins_recommender')->__('Totals area'));
				$positions[] = array('value' => 'custom', 'label' => Mage::helper('brainsins_recommender')->__('Custom position'));
				$positions[] = array('value' => '-', 'label' => Mage::helper('brainsins_recommender')->__('------------- Other positions -------------'));
				$positions[] = array('value' => 'coupon', 'label' => Mage::helper('brainsins_recommender')->__('Discount code area'));
				$positions[] = array('value' => 'shipping', 'label' => Mage::helper('brainsins_recommender')->__('Shipping estimation area'));
				$positions[] = array('value' => 'after_body_start', 'label' => Mage::helper('brainsins_recommender')->__('Top'));
				$positions[] = array('value' => 'header', 'label' => Mage::helper('brainsins_recommender')->__('Header'));
				$positions[] = array('value' => 'catalog.topnav', 'label' => Mage::helper('brainsins_recommender')->__('Navigation bar'));
				$positions[] = array('value' => 'breadcrumbs', 'label' => Mage::helper('brainsins_recommender')->__('Breadcrumb'));
				$positions[] = array('value' => 'footer', 'label' => Mage::helper('brainsins_recommender')->__('Footer'));
				$positions[] = array('value' => 'before_body_end', 'label' => Mage::helper('brainsins_recommender')->__('Bottom'));
				break;
				/*
				 case '4':
				$positions[] = array('value' => 'content', 'label' => Mage::helper('brainsins_recommender')->__('Main content'));
				$positions[] = array('value' => 'right', 'label' => Mage::helper('brainsins_recommender')->__('Right column'));
				$positions[] = array('value' => 'left', 'label' => Mage::helper('brainsins_recommender')->__('Left column'));
				$positions[] = array('value' => 'checkout.progress', 'label' => Mage::helper('brainsins_recommender')->__('Checkout progress'));
				$positions[] = array('value' => 'login', 'label' => Mage::helper('brainsins_recommender')->__('Login'));
				$positions[] = array('value' => 'billing', 'label' => Mage::helper('brainsins_recommender')->__('Billing information'));
				$positions[] = array('value' => 'shipping', 'label' => Mage::helper('brainsins_recommender')->__('Shipping information'));
				$positions[] = array('value' => 'shipping_method', 'label' => Mage::helper('brainsins_recommender')->__('Shipping method'));
				$positions[] = array('value' => 'payment', 'label' => Mage::helper('brainsins_recommender')->__('Payment information'));
				$positions[] = array('value' => 'review', 'label' => Mage::helper('brainsins_recommender')->__('Order review'));
				$positions[] = array('value' => 'custom', 'label' => Mage::helper('brainsins_recommender')->__('Custom position'));
				$positions[] = array('value' => '-', 'label' => Mage::helper('brainsins_recommender')->__('------------- Other positions -------------'));
				$positions[] = array('value' => 'billing.progress', 'label' => Mage::helper('brainsins_recommender')->__('Billing progress'));
				$positions[] = array('value' => 'shipping.progress', 'label' => Mage::helper('brainsins_recommender')->__('Shipping progress'));
				$positions[] = array('value' => 'shipping_method.progress', 'label' => Mage::helper('brainsins_recommender')->__('Shipping method progress'));
				$positions[] = array('value' => 'payment.progress', 'label' => Mage::helper('brainsins_recommender')->__('Payment progress'));
				$positions[] = array('value' => 'after_body_start', 'label' => Mage::helper('brainsins_recommender')->__('Top'));
				$positions[] = array('value' => 'header', 'label' => Mage::helper('brainsins_recommender')->__('Header'));
				$positions[] = array('value' => 'catalog.topnav', 'label' => Mage::helper('brainsins_recommender')->__('Navigation bar'));
				$positions[] = array('value' => 'breadcrumbs', 'label' => Mage::helper('brainsins_recommender')->__('Breadcrumb'));
				$positions[] = array('value' => 'footer', 'label' => Mage::helper('brainsins_recommender')->__('Footer'));
				$positions[] = array('value' => 'before_body_end', 'label' => Mage::helper('brainsins_recommender')->__('Bottom'));
				break;
				*/
			case '4':
					$positions[] = array('value' => 'content', 'label' => Mage::helper('brainsins_recommender')->__('Main content'));
				$positions[] = array('value' => 'right', 'label' => Mage::helper('brainsins_recommender')->__('Right column'));
				$positions[] = array('value' => 'left', 'label' => Mage::helper('brainsins_recommender')->__('Left column'));
				$positions[] = array('value' => 'custom', 'label' => Mage::helper('brainsins_recommender')->__('Custom position'));
				$positions[] = array('value' => '-', 'label' => Mage::helper('brainsins_recommender')->__('------------- Other positions -------------'));
				$positions[] = array('value' => 'after_body_start', 'label' => Mage::helper('brainsins_recommender')->__('Top'));
				$positions[] = array('value' => 'header', 'label' => Mage::helper('brainsins_recommender')->__('Header'));
				$positions[] = array('value' => 'catalog.topnav', 'label' => Mage::helper('brainsins_recommender')->__('Navigation bar'));
				$positions[] = array('value' => 'breadcrumbs', 'label' => Mage::helper('brainsins_recommender')->__('Breadcrumb'));
				$positions[] = array('value' => 'footer', 'label' => Mage::helper('brainsins_recommender')->__('Footer'));
				$positions[] = array('value' => 'before_body_end', 'label' => Mage::helper('brainsins_recommender')->__('Bottom'));
				break;
			case '7':
				$positions[] = array('value' => 'after_body_start', 'label' => Mage::helper('brainsins_recommender')->__('Top'));
				$positions[] = array('value' => 'header', 'label' => Mage::helper('brainsins_recommender')->__('Header'));
				$positions[] = array('value' => 'content', 'label' => Mage::helper('brainsins_recommender')->__('Main content'));
				$positions[] = array('value' => 'product_list', 'label' => Mage::helper('brainsins_recommender')->__('Product list'));
				$positions[] = array('value' => 'right', 'label' => Mage::helper('brainsins_recommender')->__('Right column'));
				$positions[] = array('value' => 'left', 'label' => Mage::helper('brainsins_recommender')->__('Left column'));
				$positions[] = array('value' => 'footer', 'label' => Mage::helper('brainsins_recommender')->__('Footer'));
				$positions[] = array('value' => 'before_body_end', 'label' => Mage::helper('brainsins_recommender')->__('Bottom'));
				$positions[] = array('value' => 'custom', 'label' => Mage::helper('brainsins_recommender')->__('Custom position'));
				$positions[] = array('value' => '-', 'label' => Mage::helper('brainsins_recommender')->__('------------- Other positions -------------'));
				$positions[] = array('value' => 'catalog.topnav', 'label' => Mage::helper('brainsins_recommender')->__('Navigation bar'));
				$positions[] = array('value' => 'breadcrumbs', 'label' => Mage::helper('brainsins_recommender')->__('Breadcrumb'));
				$positions[] = array('value' => 'toolbar', 'label' => Mage::helper('brainsins_recommender')->__('Toolbar'));
				break;
			default:
				$positions[] = array('value' => 'top', 'label' => Mage::helper('brainsins_recommender')->__('No available positions in this page'));
				break;
		}
		return $positions;
	}

	public function getConfiguration($page, $store = null)
	{
		if($store == null)
			$store = Mage::app()->getStore()->getStoreId();
		$jSonRecommendersConfig = '';
		$config = array();
		if($page == 'all')
		{
			$pages = explode(',', self::$_pages);
			foreach ($pages as $key => $_page)
			{
				$jSonRecommendersConfig = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_recommenders_'.$_page.'/recommenders_'.$_page, $store);
				$arrayDatas = unserialize($jSonRecommendersConfig);
				foreach ($arrayDatas as $key => $arrayData) {
					foreach ($arrayData as $key => $data) {
						$config[$key][] = $data;
					}
				}
			}
		}
		else
		{
			$jSonRecommendersConfig = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_recommenders_'.$page.'/recommenders_'.$page, $store);
			$arrayDatas = unserialize($jSonRecommendersConfig);
			$config = array();
			foreach ($arrayDatas as $key => $arrayData) {
				foreach ($arrayData as $key => $data) {
					$config[$key][] = $data;
				}
			}
		}

		return $this->_cleanEmpties($config);
	}

	public function getRecommendersJs($recommenders)
	{
		$recommenders_js = '';
		if($recommenders && count($recommenders) > 0)
		{
			$recommenders_js .= ',
					recommenders: [';
			foreach ($recommenders as $key => $_value)
			{
				if($_value[0] == '' || !is_numeric($_value[0]))
					continue;
				if($_value[4] != '')
				{
					$_location = $_value[4];
					$_position = $_value[3];
				}
				else {
					$_location = 'brainSINS_recommender_'.$_value[0];
					$_position = 'replace';
				}
				$recommenders_js .= "
				{
						recommenderId: ".$_value[0].",
								detailsLevel : 'high',
								location: '".$_location."',
										position: '".$_position."'
			},
												";
			}
			$recommenders_js .= ']';
		}
		return $recommenders_js;
	}

	protected function _cleanEmpties($data)
	{
		$cleanedData = array();
		foreach ($data as $key => $value) {
			if($value[0] == '')
				continue;
			else
				$cleanedData[] = $value;
		}

		return array_map('unserialize', array_unique(array_map('serialize', $cleanedData)));
	}

	public function getProductsFeed($key = '')
	{

		try {

			if($key == '')
				$key = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_general/bs_key');
			$include_oos_products = Mage::getStoreConfig('brainsins_recommender_options/product_feed/include_oos_products', Mage::app()->getStore()->getStoreId());
			$tax_included = Mage::getStoreConfig('brainsins_recommender_options/product_feed/tax_included', Mage::app()->getStore()->getStoreId());
			$special_price = Mage::getStoreConfig('brainsins_recommender_options/product_feed/special_price', Mage::app()->getStore()->getStoreId());
			$desc_attribute = Mage::getStoreConfig('brainsins_recommender_options/product_feed/product_description_attribute', Mage::app()->getStore()->getStoreId());

			$pDomBrain = new DOMDocument('1.0','UTF-8');
			$pDomBrain->formatOutput = true;
			$pDomBrain->xmlStandalone = true;

			$pRecsins = $pDomBrain->createElement('recsins');
			$pRecsins->setAttribute('version', '0.1');
			$pDomBrain->appendChild($pRecsins);

			$pEntities = $pDomBrain->createElement('entities');
			$pRecsins->appendChild($pEntities);

			$rows = 0;

			$languages = $this->getStores($key);
			$currencies = $this->getCurrencies($key);
			// $products = Mage::getResourceModel('catalog/product_collection')
			//                 ->getCollection()
			//                 ->addAttributeToSelect('entity_id')
			//                 ->addFieldToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
			//                 ->addFieldToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
			//                 ->addAttributeToSort('entity_id','asc'); //Tablas EAV
			$products = Mage::getModel('catalog/product')
			->getCollection()
			->addAttributeToSelect('entity_id')
			->addFieldToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
			->addFieldToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
			->addAttributeToSort('entity_id','asc'); //Tablas flat

			if(!$desc_attribute || $desc_attribute == '')
				$desc_attribute = 'description';

			foreach ($products as $key => $entity)
			{
				try {
					$product = Mage::getModel('catalog/product')->load($entity->getId());
					if(Mage::app()->getRequest()->getControllerName() != 'feeds')
					{
						$offline_stock_status = Mage::getModel('cataloginventory/stock_item') ->loadByProduct($product)->getIsInStock();
						if($include_oos_products == '0' && $offline_stock_status == '0')
							continue;
					}
					else
					{
						if($include_oos_products == '0' && !$product->isSaleable())
							continue;
					}
					$pEntity = $pDomBrain->createElement('entity');
					$pEntity->setAttribute('name', 'product');
					$pEntities->appendChild($pEntity);

					$pIDProd = $pDomBrain->createElement('property');
					$pIDProd->setAttribute('name', 'idProduct');
					$cdata = $pDomBrain->createTextNode($product->getId());
					$pIDProd->appendChild($cdata);
					$pEntity->appendChild($pIDProd);

					$pMPName = $pDomBrain->createElement('multi_property');
					$pMPName->setAttribute('name', 'name');

					$pMPDesc = $pDomBrain->createElement('multi_property');
					$pMPDesc->setAttribute('name', 'description');

					$pMPUrl = $pDomBrain->createElement('multi_property');
					$pMPUrl->setAttribute('name', 'url');

					foreach ($languages as $store_id => $lang_code) {
						$product_lang = Mage::getModel('catalog/product')
						->setStoreId($store_id)
						->load($product->getId());
						//$lang_code = explode('_', $lang_code);
						$pLangName = $pDomBrain->createElement('property');
						$pLangName->setAttribute('lang', $lang_code);
						$cdata = $pDomBrain->createCDATASection($product_lang->getName());
						$pLangName->appendChild($cdata);
						$pMPName->appendChild($pLangName);

						$pLangDesc = $pDomBrain->createElement('property');
						$pLangDesc->setAttribute('lang', $lang_code);
						$cdata = $pDomBrain->createCDATASection(Mage::getResourceModel('catalog/product')
								->getAttributeRawValue($product_lang->getId(), $desc_attribute, $store_id));
						$pLangDesc->appendChild($cdata);
						$pMPDesc->appendChild($pLangDesc);

						$pLangLink = $pDomBrain->createElement('property');
						$pLangLink->setAttribute('lang', $lang_code);
						$product_link = preg_replace("/[\?&]?___store=[^\?&]*/", "", $product_lang->getProductUrl());
						$cdata = $pDomBrain->createCDATASection($product_link);
						$pLangLink->appendChild($cdata);
						$pMPUrl->appendChild($pLangLink);
					}
					$pEntity->appendChild($pMPName);
					$pEntity->appendChild($pMPDesc);
					$pEntity->appendChild($pMPUrl);

					$pPrice = $pDomBrain->createElement('property');
					$pPrice->setAttribute('name', 'price');

					if($tax_included == '1' && $special_price == '1')
						$product_price = Mage::helper('tax')->getPrice($product, $product->getFinalPrice(), true, null, null, null, null, false);
					elseif($tax_included == '1' && $special_price == '0')
					$product_price = Mage::helper('tax')->getPrice($product, $product->getPrice(), true, null, null, null, null, false);
					if($tax_included == '0' && $special_price == '1')
						$product_price = Mage::helper('tax')->getPrice($product, $product->getFinalPrice(), true, null, null, null, null, true);
					elseif($tax_included == '0' && $special_price == '0')
					$product_price = Mage::helper('tax')->getPrice($product, $product->getPrice(), true, null, null, null, null, true);

					if($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE)
					{
						if($tax_included == '1' && $special_price == '1')
							$product_price = Mage::helper('tax')->getPrice($product, $this->getBundlePrice($product, 'min', 1), true, null, null, null, null, false);
						elseif($tax_included == '1' && $special_price == '0')
						$product_price = Mage::helper('tax')->getPrice($product, $this->getBundlePrice($product, 'min', 1), true, null, null, null, null, false);
						if($tax_included == '0' && $special_price == '1')
							$product_price = Mage::helper('tax')->getPrice($product, $this->getBundlePrice($product, 'min', 0), true, null, null, null, null, true);
						elseif($tax_included == '0' && $special_price == '0')
						$product_price = Mage::helper('tax')->getPrice($product, $this->getBundlePrice($product, 'min', 0), true, null, null, null, null, true);
					}

					$cdata = $pDomBrain->createTextNode(number_format($product_price, 2, '.', ''));
					$pPrice->appendChild($cdata);
					$pEntity->appendChild($pPrice);

					if($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE)
					{
						$product_final_price = $this->getBundlePrice($product, 'min', 1);
						$product_regular_price = $this->getBundlePrice($product, 'min', 1);
					}
					else
					{
						$product_final_price = $product->getFinalPrice();
						$product_regular_price = $product->getPrice();
					}

					try
					{
						$pMPCur = $pDomBrain->createElement('multi_property');
						$pMPCur->setAttribute('name', 'multiprice');

						foreach ($currencies as $k => $curr){
							$baseCurrency = Mage::app()->getStore()->getBaseCurrencyCode();
							$targetCurrency = Mage::getModel('directory/currency')->load($curr);
							$pPrice = $pDomBrain->createElement('property');
							$pPrice->setAttribute('currency', $curr);
							$price_converted = number_format(Mage::helper('directory')->currencyConvert($product_final_price, $baseCurrency, $targetCurrency), 2, '.', '');
							$cdata = $pDomBrain->createTextNode($price_converted);
							$pPrice->appendChild($cdata);
							$pMPCur->appendChild($pPrice);
						}
						$pEntity->appendChild($pMPCur);
						unset($targetCurrency);

						$pOPCur = $pDomBrain->createElement('multi_property');
						$pOPCur->setAttribute('name', 'originalPrice');

						foreach ($currencies as $k => $curr){
							$baseCurrency = Mage::app()->getStore()->getBaseCurrencyCode();
							$targetCurrency = Mage::getModel('directory/currency')->load($curr);
							$pPrice = $pDomBrain->createElement('property');
							$pPrice->setAttribute('currency', $curr);
							$price_converted = number_format(Mage::helper('directory')->currencyConvert($product_regular_price, $baseCurrency, $targetCurrency), 2, '.', '');
							$cdata = $pDomBrain->createTextNode($price_converted);
							$pPrice->appendChild($cdata);
							$pOPCur->appendChild($pPrice);
						}
						$pEntity->appendChild($pOPCur);
						unset($targetCurrency);
					}
					catch(Exception $e)
					{
						Mage::throwException(Mage::helper('brainsins_recommender')->__('ERROR IN CURRENCY CONVERT. ALL CURRENCY RATES ARE DEFINED?'));
					}

					$imageUrl = null;

					$image_width = Mage::getStoreConfig('brainsins_recommender_options/product_feed/product_image_resize_width', Mage::app()->getStore()->getStoreId());
					$image_height = Mage::getStoreConfig('brainsins_recommender_options/product_feed/product_image_resize_height', Mage::app()->getStore()->getStoreId());

					if (!$image_width || $image_width == "" || $image_width == "0" || !is_numeric($image_width)) {
						$image_width = null;
					}

					if (!$image_height || $image_height == "" || $image_height == "0" || !is_numeric($image_height)) {
						$image_height = null;
					}


					if (!$image_width && !$image_height) {
						$imageUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product'.$product->getImage();
					} else {
						$imageUrl = (string) Mage::helper('catalog/image')->init($product, "image")->resize($image_width, $image_height);
					}

					// $url = (string) Mage::helper('catalog/image')->init($product, "small_image")->resize($width, $height);



					//$imageUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product'.$product->getImage();
					$pImage = $pDomBrain->createElement('property');
					$pImage->setAttribute('name', 'imageUrl');
					$cdata = $pDomBrain->createCDATASection($imageUrl);
					$pImage->appendChild($cdata);
					$pEntity->appendChild($pImage);

					$pCategory = $pDomBrain->createElement('property');
					$pCategory->setAttribute('name', 'categories');
					$cdata = $pDomBrain->createTextNode(implode(",", $product->getCategoryIds()));
					$pCategory->appendChild($cdata);
					$pEntity->appendChild($pCategory);

					$rows++;
					$image = null;
					unset($image);

					$imageObj = null;
					unset($imageObj);

					unset($product);
				} catch (Exception $e2) {
					$pEntity = $pDomBrain->createElement('entity-error');
					$pEntity->setAttribute('name', 'product-error');
					$pEntities->appendChild($pEntity);
				}
			}
			$feed = $pDomBrain->saveXML();
			$products = null;unset($products);unset($pDomBrain);

		} catch (Exception $e3) {
			return $e3;
		}

		return $feed;
	}

	public function updateCartInBrainsins($cart, $special = false)
	{
		if (!is_object($cart))
			return;

		$cartId = $cart->getId();

		if (!$cartId) {
			return;
		}

		$ruta = "order/trackOrder.xml?";
		$url = self::getApiUrl().$ruta."token=".Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_general/bs_key', Mage::app()->getStore()->getStoreId());

		$cartXML = new DOMDocument('1.0','UTF-8');
		$cartXML->xmlStandalone = true;

		$pRecsins = $cartXML->createElement('recsins');
		$pRecsins->setAttribute('version', '0.1');
		$cartXML->appendChild($pRecsins);

		$pOrders = $cartXML->createElement('orders');

		$pOrder = $cartXML->createElement('order');
		$pIdBuyer = $cartXML->createElement('idBuyer', $this->_getUser());
		$pOrder->appendChild($pIdBuyer);

		$pCurrency = $cartXML->createElement('idCurrency', Mage::app()->getStore()->getCurrentCurrencyCode());
		$pOrder->appendChild($pCurrency);

		$pOrders->appendChild($pOrder);

		$pRecsins->appendChild($pOrders);

		$pProducts = $cartXML->createElement('products');
		$pOrder->appendChild($pProducts);

		if($special)
			$items = $cart->getAllVisibleItems();
		else
			$items = $cart->getItems();
		
		$cartItems = Array();
				
		foreach($items as $item) {

            $cartItem = Array();

            $price = $item->getPriceInclTax();

			if ($item->getParentItem()) {
				$item = $item->getParentItem();
			}
			
			$id = $item->getProductId();
			
//  			if ($item->getParentItemId() && $item->getParentItemId() != "") {
//  				//Mage::log($item->getProduct()->getSuperProduct()->getId());
//  				$id = $item->getParentItem()->getProductId();
//  			}
			
			$qty = $item->getQty();
			
			//get parent if exists -> ensure not single products that belong to configurable exist
			$configurable_product_model = Mage::getModel('catalog/product_type_configurable');
			$parentIdArray = $configurable_product_model->getParentIdsByChild($id);
			
			
			if (count($parentIdArray) > 0) {
				$id = $parentIdArray[0];
			}
			
			
// 			Mage::log("-----");
// 			Mage::log($item->getProductId());
// 			Mage::log("type : " . $item->getProductType());
// 			Mage::log($item->getProduct()->getName());
// 			Mage::log("parent : " . ($item->getParentItem() ? $item->getParentItem()->getProductId() : "-"));
// 			Mage::log("qty is " . $qty);
// 			Mage::log("-----");

            $cartItem["id"] = $id;
            $cartItem["qty"] = $qty;
            $cartItem["price"] = $price;

            $cartItems[] = $cartItem;

			//if (array_key_exists($id, $cartItems)) {
            //	$cartItems[$id] = $cartItems[$id] + $qty;
			//} else {
			//	$cartItems[$id] = $qty;
			//}
		}

		//Mage::log($cartItems);
		
		foreach ($cartItems as $cartItem) {
			$pProduct = $cartXML->createElement('product');

			$pIdProduct = $cartXML->createElement('idProduct', $cartItem["id"]);
			$pProduct->appendChild($pIdProduct);

 			$pPrice = $cartXML->createElement('price', number_format($cartItem["price"], 2, '.', ''));
 			$pProduct->appendChild($pPrice);

			$pQuantity = $cartXML->createElement('quantity', $cartItem["qty"]);
			$pProduct->appendChild($pQuantity);

			$pProducts->appendChild($pProduct);
			$cartId .= ":".$id.":".$qty;
		}



		$lastTrackedCart = Mage::getSingleton('core/session')->getBrainsinsLastCartTracked();
// 		Mage::log($cartId);
		if ($cartId == $lastTrackedCart) {
			return;
		} else {
			Mage::getSingleton('core/session')->setBrainsinsLastCartTracked($cartId);
		}

		$content = $cartXML->saveXML($cartXML->documentElement);
// 		Mage::log($content);
		$result = $this->_sendBrainsinsWS($url, $content);
// 		Mage::log($result);
		return $result;
	}

	public function onEndCheckout($order) {
		$idsMultipleArray = Mage::getSingleton('core/session')->getOrderIds(true);
		$firstElement = true;
		$total = 0;
				
		$ruta = "purchase/close?";
		$url = self::getApiUrl().$ruta."token=".Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_general/bs_key', Mage::app()->getStore()->getStoreId());

		if ($idsMultipleArray && is_array($idsMultipleArray)) {
			foreach ($idsMultipleArray as $id) {
				$order = Mage::getModel('sales/order')->loadByIncrementId($id);
				$total += $order->getBaseSubtotalInclTax();
			}
		} else {
			$total = $order->getBaseSubtotalInclTax();
		}

		$email = $order->getCustomerEmail();
		$url .= "&email=$email&amount=$total";
		
		if (array_key_exists("bsCoId", $_COOKIE)) {
			$coId = $_COOKIE['bsCoId'];
			if ($coId) {
				$url .= "&cookieId=$coId";
			}
		}
		
		$result = $this->_sendBrainsinsWS($url);

		return $result;
	}

	public function onPayment($order) {
		$idsMultipleArray = Mage::getSingleton('core/session')->getOrderIds(true);
		$firstElement = true;
		$total = 0;

		$ruta = "purchase/payment.json?";
		$url = self::getApiUrl().$ruta."token=".Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_general/bs_key', Mage::app()->getStore()->getStoreId());

		if ($idsMultipleArray && is_array($idsMultipleArray)) {
			foreach ($idsMultipleArray as $id) {
				$order = Mage::getModel('sales/order')->loadByIncrementId($id);
				$total += $order->getBaseSubtotalInclTax();
			}
		} else {
			$total = $order->getBaseSubtotalInclTax();
		}

		$email = $order->getCustomerEmail();
		$url .= "&email=$email&amount=$total";
		$result = $this->_sendBrainsinsWS($url);

		return $result;
	}

	protected function _sendBrainsinsWS($url, $content = "", $contentType = "application/xml", $post = true)
	{
// 		Mage::log(" === WS ==============================");
// 		Mage::log("url : " . $url);
// 		Mage::log("content : " . $content);
		
		if(function_exists('curl_init')) {
			$ch = curl_init($url);
			//$logfh = fopen(dirname(__FILE__) . "/sendBrainsinsWS.log", 'a+');
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

			if ($post) {
				curl_setopt($ch, CURLOPT_POST, 1);
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: ' . $contentType));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
			curl_setopt($ch, CURLOPT_VERBOSE, true);
			//curl_setopt($ch, CURLOPT_STDERR, $logfh);
			//fwrite($logfh, $content);
			$response = curl_exec($ch);
			curl_close($ch);
			//fclose($logfh);
// 			Mage::log($response);
		} else {
			$opts = array('http' =>
					array(
							'method'  => 'POST',
							'header'  => 'Content-type: text/xml',
							'content' => $content
					)
			);
			$context  = stream_context_create($opts);
			$response = file_get_contents($url, false, $context);
		}

		return $response;
	}

	protected function _getUser()
	{
		if (isset($_COOKIE['bsUl'])  && $_COOKIE['bsUl'] == 1)
			return $_COOKIE['bsUId'];
		elseif (isset($_COOKIE['bsUl'])  && $_COOKIE['bsUl'] == 0)
		return $_COOKIE['bsCoId'];
		else
			return;
	}

	public function getStores($key)
	{
		$stores = Mage::app()->getStores();
		$store_ids = array();
		foreach ($stores as $k => $store)
		{
			$_storeId = $store->getStoreId();
			$bskey = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_general/bs_key', $_storeId);
			if($key == $bskey)
			{
				//$store_ids[$_storeId] = Mage::getStoreConfig('general/locale/code', $_storeId);
				$store_ids[$_storeId] = $store->getCode();
			}
		}

		/*
		 $website = Mage::app()->getWebsite($website_id);
		foreach ($website->getGroups() as $group)
		{
		$stores = $group->getStores();
		foreach ($stores as $store)
		{
		if($store->getIsActive())
			$store_ids[Mage::getStoreConfig('general/locale/code', $store->getId())] = $store->getId();
		}
		}
		*/
		return $store_ids;
	}

	protected function getCurrencies($key)
	{
		$stores = Mage::app()->getStores();
		$currencies_ids = array();
		foreach ($stores as $k => $store)
		{
			$_storeId = $store->getStoreId();
			$bskey = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_general/bs_key', $_storeId);
			if($key == $bskey)
			{
					
				$currencies_ids[$_storeId] = Mage::app()->getStore($_storeId)->getCurrentCurrencyCode();
			}
		}
		return $currencies_ids;
	}

	public function getBundlePrice($_product, $return_type, $tax)
	{
		return Mage::getModel('bundle/product_price')->getTotalPrices($_product, $return_type, $tax);
	}
	
	public function getProductImage($product, $width = false, $height = false) {
		$imageUrl = null;
		$image_width = null;
		$image_height = null;
		
		if (!$width) {
			$image_width = Mage::getStoreConfig('brainsins_recommender_options/product_feed/product_image_resize_width', Mage::app()->getStore()->getStoreId());
		} else {
			$image_width = $width;
		}
		
		if (!$height) {
			$image_height = Mage::getStoreConfig('brainsins_recommender_options/product_feed/product_image_resize_height', Mage::app()->getStore()->getStoreId());
		} else {
			$image_height = $height;
		}
		
		if (!$image_width || $image_width == "" || $image_width == "0" || !is_numeric($image_width)) {
			$image_width = null;
		}
		
		if (!$image_height || $image_height == "" || $image_height == "0" || !is_numeric($image_height)) {
			$image_height = null;
		}
		
		
		if (!$image_width && !$image_height) {
			$imageUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product'.$product->getImage();
		} else {
			$imageUrl = (string) Mage::helper('catalog/image')->init($product, "image")->resize($image_width, $image_height);
		}
		
		return $imageUrl;
	}


}
