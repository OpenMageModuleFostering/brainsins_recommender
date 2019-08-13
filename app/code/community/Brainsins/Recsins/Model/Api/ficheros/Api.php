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

class Brainsins_Recsins_Model_Api_Api extends Mage_Api_Model_Resource_Abstract {

	public function test($param) {
		return array("text" => "hey, i was invoked and i return something! like " . $param);
	}

	public function storeList() {
		$stores = Mage::app()->getStore()->getCollection();
		$result = array();
		foreach($stores as $store) {
			$result[$store->getId()] = array("id" => $store->getId(), "code" => $store->getCode());
		}
		return $result;
	}

	public function productInfo($productId, $store = null, $options = array(), $filter = array()) {

		$productsApi = new Brainsins_Recsins_Model_Api_ProductsApi();

		return ($productsApi->bsInfo($productId, $store));
	}

	public function productCount() {
		return array(Mage::getModel("catalog/product")->getCollection()
		->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
		->count());
	}

	public function productPage($numPage, $size, $visibility = 2, $options = array(), $filter = array()) {
		
		$visibility = 2;
		$visibilityComparator = "ge";
		
		if (array_key_exists("visibility", $options)) {
			$visibility = $options["visibility"];
		}
		
		if (array_key_exists("visibilityComparator", $options)) {
			$visibilityComparator = $options["visibilityComparator"];
		}
		
		
		$products = Mage::getModel('catalog/product')->getCollection()
		->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
		->addAttributeToSelect('id')->
		addAttributeToSelect('visibility')->
		addAttributeToSelect('store_ids')->
		setPage($numPage, $size);

		$result = array();
		$productsApi = new Brainsins_Recsins_Model_Api_ProductsApi();

		foreach($products as $product) {
			$id = $product->getId();
			$prodVisibility = $product->getVisibility();

			if (
			$visibilityComparator == "eq" && $prodVisibility == $visibility ||
			$visibilityComparator == "ne" && $prodVisibility != $visibility ||
			$visibilityComparator == "gt" && $prodVisibility > $visibility ||
			$visibilityComparator == "ge" && $prodVisibility >= $visibility ||
			$visibilityComparator == "lt" && $prodVisibility < $visibility ||
			$visibilityComparator == "le" && $prodVisibility <= $visibility
			) {
				$stores = $product->getStoreIds();

				if (isset($stores) && is_array($stores)) {
					$result[$id] = array();

					foreach($stores as $storeId) {
						$product->setStoreId($storeId);
						$prodInfo = $productsApi->bsInfo($id, $storeId, $options, $filter);
						$result[$id][$storeId] = $prodInfo;
					}
				}
			}
		}
		return $result;
	}

	public function userCount() {
		return array(Mage::getModel("customer/customer")->getCollection()->count());
	}

	public function userPage($numPage, $size) {
		$customers = Mage::getModel("customer/customer")->getCollection()->setPage($numPage, $size);
		$result = array();

		foreach($customers as $customer) {
			$customerInfo = array();
			$customerInfo['id'] = $customer->getId();

			$subscriber = Mage::getModel("newsletter/subscriber");
			$subscriber->loadByCustomer($customer);

			$email = $customer->getEmail();
			$subscribed = "0";
			if ($subscriber->isSubscribed()) {
				$subscribed = "1";
			} 
			
			$customerInfo['email'] = $email;
			$customerInfo['bs_lang_code'] = Mage::app()->getStore($customer->getStoreId())->getCode();
			$customerInfo['bs_is_subscribed'] = $subscribed;
			$result[] = $customerInfo;
		}

		return $result;
	}

	public function getExtensionOptions() {

		$result = array();

		$version = Mage::getStoreConfig('brainsins/BS_VERSION');

		if (isset($version)) {
			$result['version'] = $version;
		} else {
			$result['version'] = "0";
		}

		$key = Mage::getStoreConfig('brainsins/BSKEY');

		if (isset($key)) {
			$result['key'] = $key;
		} else {
			$result['key'] = "0";
		}

		$enabledValue = Mage::getStoreConfig('brainsins/BS_ENABLED');

		if (isset($enabledValue)) {
			$result['enabled'] = $enabledValue;
		} else {
			$result['enabled'] = "0";
		}

		$homeSelected = Mage::getStoreConfig('brainsins/BS_HOME_RECOMMENDER');

		if (isset($homeSelected)) {
			$result['home_recommender'] = $homeSelected;
		} else {
			$result['home_recommender'] = "0";
		}

		$categorySelected = Mage::getStoreConfig('brainsins/BS_CATEGORY_RECOMMENDER');

		if (isset($categorySelected)) {
			$result['category_recommender'] = $categorySelected;
		} else {
			$result['category_recommender'] = "0";
		}

		$productSelected = Mage::getStoreConfig('brainsins/BS_PRODUCT_RECOMMENDER');

		if (isset($productSelected)) {
			$result['product_recommender'] = $productSelected;
		} else {
			$result['product_recommender'] = "0";
		}


		$cartSelected = Mage::getStoreConfig('brainsins/BS_CART_RECOMMENDER');

		if (isset($cartSelected)) {
			$result['cart_recommender'] = $cartSelected;
		} else {
			$result['cart_recommender'] = "0";
		}

		$checkoutSelected = Mage::getStoreConfig('brainsins/BS_CHECKOUT_RECOMMENDER');

		if (isset($checkoutSelected)) {
			$result['checkout_recommender'] = $checkoutSelected;
		} else {
			$result['checkout_recommender'] = "0";
		}


		$outOfStockSelected = Mage::getStoreConfig('brainsins/BS_SEND_OUT_OF_STOCK_PRODUCTS');

		if (isset($outOfStockSelected)) {
			$result['upload_out_of_stock'] = $outOfStockSelected;
		} else {
			$result['upload_out_of_stock'] = "0";
		}

		$imageSelectedOption = Mage::getStoreConfig('brainsins/BS_IMAGE_RESIZE');

		if (isset($imageSelectedOption)) {
			$result['image_option'] = $imageSelectedOption;
		} else {
			$result['image_option'] = "image_no_resize";
		}

		$imageSelectedWidth = Mage::getStoreConfig('brainsins/BS_IMAGE_RESIZE_WIDTH');

		if (isset($imageSelectedWidth)) {
			$result['image_resize_width'] = $imageSelectedWidth;
		} else {
			$result['image_resize_width'] = "0";
		}

		$imageSelectedHeigth = Mage::getStoreConfig('brainsins/BS_IMAGE_RESIZE_HEIGTH');

		if (isset($imageSelectedHeigth)) {
			$result['image_resize_heigth'] = $imageSelectedHeigth;
		} else {
			$result['image_resize_heigth'] = "0";
		}

		$specialPriceSelectedOption = Mage::getStoreConfig('brainsins/BS_USE_SPECIAL_PRICE');

		if (isset($specialPriceSelectedOption)) {
			$result['use_special_price'] = $specialPriceSelectedOption;
		} else {
			$result['use_special_price'] = "0";
		}
		
		$taxPriceSelectedOption = Mage::getStoreConfig('brainsins/BS_TAX_PRICE');
		
		if (isset($taxPriceSelectedOption)) {
			$result['tax_price'] = $taxPriceSelectedOption;
		} else {
			$result['tax_price'] = "tax_price_equal";
		}

		return $result;
	}

	public function notifyVersion($version) {
		if (isset ($version)) {
			Mage::getModel('core/config')->saveConfig('brainsins/BS_LAST_AVAILABLE_VERSION', $version);
		}
	}
}