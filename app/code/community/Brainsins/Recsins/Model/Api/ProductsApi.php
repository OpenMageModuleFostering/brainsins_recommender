<?php

/*
 * BrainSINS' Magento Extension allows to integrate the BrainSINS
* personalized product recommendations into a Magento Store.
* Copyright (c) 2011 Social Gaming Platform S.R.L.
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
*
*/

class Brainsins_Recsins_Model_Api_ProductsApi extends Mage_Catalog_Model_Product_Api {


	protected function _getProduct($productId, $storeId = null, $identifierType = null)
	{
		$product = Mage::getModel('catalog/product');

		if (isset($storeId) && $storeId != null) {
			$product->setStoreId($storeId);
		}

		$product->load($productId);
		return $product;
	}

	protected function _getRealImageUrl($product, $store) {
		$prodImage = $product->getSmallImage();

		$img = Mage::getModel("catalog/product_image");
		$img->setDestinationSubdir("small_image");
		$img->setBaseFile($prodImage);

		$baseDir = Mage::getBaseDir('media');
		$path = str_replace($baseDir . DS, "", $img->getBaseFile());
		return $store->getBaseUrl('media') . str_replace(DS, '/', $path) ;
	}

	private function checkOption($option, $options) {
		return array_key_exists($option, $options) && $options[$option] == "1";
	}

	public function info($productId, $storeId = null, $options = array(), $filter = array())
	{
		$product = $this->_getProduct($productId, $storeId, $identifierType);
		$store = Mage::app()->getStore($storeId);

		if (!$product->getId()) {
			$this->_fault('not_exists');
		}

		$result = array( // Basic product data
	            'product_id' => $product->getId(),
	            'sku'        => $product->getSku(),
	            'set'        => $product->getAttributeSetId(),
	            'type'       => $product->getTypeId(),
	            'categories' => $product->getCategoryIds(),
	            'websites'   => $product->getWebsiteIds()
		);

		foreach ($product->getTypeInstance(true)->getEditableAttributes($product) as $attribute) {
			if ($this->_isAllowedAttribute($attribute, $attributes) && !in_array($attribute->getAttributeCode(), $filter)) {
				$result[$attribute->getAttributeCode()] = $product->getData(
				$attribute->getAttributeCode());
			}
		}

		//option dependent values. Every non-standard value starts with bs_

		if ($this->checkOption("getPricesInStoreCurrency", $options)) {
			$result["bs_currency_final_price"] = $store->getBaseCurrency()->convert($product->getFinalPrice(), $store->getCurrentCurrencyCode());
			$result["bs_currency_special_price"] = $store->getBaseCurrency()->convert($product->getSpecialPrice(), $store->getCurrentCurrencyCode());
			$result["bs_currency_price"] = $store->getBaseCurrency()->convert($product->getPrice(), $store->getCurrentCurrencyCode());
				
			$price = $store->getBaseCurrency()->convert($product->getPrice(), $store->getCurrentCurrencyCode());
			$specialPrice = $store->getBaseCurrency()->convert($product->getSpecialPrice(), $store->getCurrentCurrencyCode());
				
			$plusTaxPrice = Mage::helper("tax")->getPrice($product, $price, true, null, null, null, $store, false);
			$minusTaxPrice = Mage::helper("tax")->getPrice($product, $price, false, null, null, null, $store, true);
			$plusTaxSpecialPrice = Mage::helper("tax")->getPrice($product, $specialPrice, true, null, null, null, $store, false);
			$minusTaxSpecialPrice = Mage::helper("tax")->getPrice($product, $specialPrice, false, null, null, null, $store, true);
				
			$result['bs_price_currency_plus_tax'] = $plusTaxPrice;
			$result['bs_price_currency_minus_tax'] = $minusTaxPrice;
			$result['bs_special_currency_price_plus_tax'] = $plusTaxSpecialPrice;
			$result['bs_special_currency_price_minus_tax'] = $minusTaxSpecialPrice;
		}
		
		if ($this->checkOption("getConversionRates", $options)) {
			$currencies = Mage::app()->getStore()->getAvailableCurrencyCodes(true);
			$currencyRates = array();
			foreach($currencies as $currency) {
				$currencyRates[$currency] = $store->getBaseCurrency()->getRate($currency);
			}
			$result['bs_currency_rates'] = $currencyRates;
		} else {
			$result['options'] = $options;
		}

		if ($this->checkOption("getAllImages", $options)) {
			//add images and url info (store dependent)
			$prodCachedImageUrl = (string)Mage::helper('catalog/image')->init($product, "small_image");
			$prodImageUrl = $this->_getRealImageUrl($product, $store);
			$imageControllerUrl = $store->getBaseUrl() . "recsins/index/getRecProdImg?id=" . $product->getId();

			$result['bs_cached_image'] = $prodCachedImageUrl;
			$result['bs_real_image'] = $prodImageUrl;
			$result['bs_controller_image'] = $imageControllerUrl;
		}

		if ($this->checkOption("getMinimalPrices", $options)) {
			$minPrice = 0;

			if ($product->getTypeId() == "bundle") {
				list($minPrice, $maxPrice) = $product->getPriceModel()->getPrices($product);
				if (isset($minPrice)) {
					$plusTaxPrice = Mage::helper("tax")->getPrice($product, $minPrice, true, null, null, null, $store, false);
					$minusTaxPrice = Mage::helper("tax")->getPrice($product, $minPrice, false, null, null, null, $store, true);
					$result['bs_min_price'] = $minPrice;
					$result['bs_min_price_plus_tax'] = $plusTaxPrice;
					$result['bs_min_price_minus_tax'] = $minusTaxPrice;
				} else {
					$result['bs_min_price'] = 0;
					$result['bs_min_price_plus_tax'] = 0;
					$result['bs_min_price_minus_tax'] = 0;
				}
			}
		}

		if ($this->checkOption("getUrl", $options)) {
			$url = $store->getBaseUrl() . $product->getUrlPath();

			$result['bs_product_url'] = $url;//$product->getProductUrl();
		}

		if ($this->checkOption("getTags", $options)) {
			$tags = Mage::getModel("tag/tag")->getResourceCollection()
			->joinRel()
			->addProductFilter($productId)
			->addTagGroup()
			->load();

			$result['tags'] = array();
			foreach($tags as $tag) {
				$result['bs_tags'][] = $tag->getName();
			}
		}

		if ($this->checkOption("getManufacter", $options)) {
			$result['bs_manufacter_name'] = $product->getAttributeText('manufacturer');
		}

		if ($this->checkOption("getTaxedPrices", $options)) {
			$price = $minPrice == 0 ? $product->getPrice() : $minPrice;
			$specialPrice = 0;

			$specialPrice = $product->getSpecialPrice();
			if (!isset($specialPrice) || $specialPrice == null) {
				$specialPrice = 0;
			}

			$store = Mage::app()->getStore($storeId);

			$plusTaxPrice = Mage::helper("tax")->getPrice($product, $price, true, null, null, null, $store, false);
			$minusTaxPrice = Mage::helper("tax")->getPrice($product, $price, false, null, null, null, $store, true);
			$plusTaxSpecialPrice = Mage::helper("tax")->getPrice($product, $specialPrice, true, null, null, null, $store, false);
			$minusTaxSpecialPrice = Mage::helper("tax")->getPrice($product, $specialPrice, false, null, null, null, $store, true);

			$result['bs_price_plus_tax'] = $plusTaxPrice;
			$result['bs_price_minus_tax'] = $minusTaxPrice;
			$result['bs_special_price_plus_tax'] = $plusTaxSpecialPrice;
			$result['bs_special_price_minus_tax'] = $minusTaxSpecialPrice;
		}

		if ($this->checkOption("getChildrenStock", $options)) {
			if ($product->getTypeId() == "configurable") {
				//sometimes "salable" attribute is not good enought to determine wether a configurable product
				//is out of stock. Just iterate through all the sub-products and check their stock status.
				$inStock = false;
				$children = $product->getTypeInstance()->getUsedProducts();
				foreach($children as $child) {
					$inStock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($child)->getIs_in_stock() == "1";
					if ($inStock) {
						break;
					}
				}

				$result['bs_configurable_in_stock'] = $inStock ? "1" : "0";
			} else {
				//returns a value, but should not be used as product is not configurable
				$result['bs_configurable_in_stock'] = "0";
			}
		}

		if ($this->checkOption("getStock", $options)) {
			$result['bs_in_stock'] = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getIs_in_stock() !== '0' ? "1" : "0";
		}

		if ($this->checkOption("getSalable", $options)) {
			if (method_exists($product, "isAvailable")) {
				$result['bs_is_salable'] = $product->isAvailable() ? "1" : "0";
			} else if (method_exists($product, "isSalable")) {
				$result['bs_is_salable'] = $product->isSalable() ? "1" : "0";
			}
		}

		if ($this->checkOption("getVisibility", $options)) {
			$visibility = $product->getVisibility();
			$result["bs_visibility"] = $visibility;
		}

		return $result;
	}

	public function bsInfo($productId, $storeId, $options = array(), $filter = array()) {

		$result = $this->info($productId, $storeId, $options, $filter);
		return $result;
	}

	public function items($filters = null, $store = null)
	{
		$collection = Mage::getModel('catalog/product')->getCollection()
		->addStoreFilter($this->_getStoreId($store))
		->addAttributeToSelect('name')->addAttributeToSelect('visibility');

		if (is_array($filters)) {
			try {
				foreach ($filters as $field => $value) {
					if (isset($this->_filtersMap[$field])) {
						$field = $this->_filtersMap[$field];
					}

					$collection->addFieldToFilter($field, $value);
				}
			} catch (Mage_Core_Exception $e) {
				$this->_fault('filters_invalid', $e->getMessage());
			}
		}

		$result = array();

		foreach ($collection as $product) {
			//            $result[] = $product->getData();
			$result[] = array( // Basic product data
		                'product_id' => $product->getId(),
		                'sku'        => $product->getSku(),
		                'name'       => $product->getName(),
		                'set'        => $product->getAttributeSetId(),
		                'type'       => $product->getTypeId(),
		                'category_ids'       => $product->getCategoryIds(),
		                'visibility' => $product->getVisibility()
			);
		}
		return $result;
	}
}