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
*  BrainSINS' Magento Extension is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  You should have received a copy of the GNU General Public License
*  along with BrainSINS' Magento Extension.  If not, see
*  <http://www.gnu.org/licenses/>.
*
*  Please do not hesitate to contact us at info@brainsins.com
*
*/

/*
 * This block is attached to the footer of all front-end pages
*/

class Brainsins_Recsins_Block_Recsins extends Mage_Core_Block_Abstract {

	//private $dev = true;
	private $dev = false;

	protected function _construct() {

		$userId = Mage::getSingleton('customer/session')->getCustomer()->getId();

		if (!isset($userId) || !$userId) {
			if (array_key_exists('coId', $_COOKIE)) {
				$userId = $_COOKIE['coId'];
			} else {
				$userId = '0';
			}
		}
	}

	public function _prepareLayout() {
		return parent::_prepareLayout();
	}

	public function _loadCache() {
		//always force to reload cache
		return false;
	}

	public function getRecsins() {
		if (!$this->hasData('recsins')) {
			$this->setData('recsins', Mage::registry('recsins'));
		}
		return $this->getData('recsins');
	}

	public function getUserId() {

		$userId = Mage::getSingleton('customer/session')->getCustomer()->getId();
		if (!isset($userId) || !$userId) {
			if (array_key_exists('coId', $_COOKIE)) {
				$userId = $_COOKIE['coId'];
			} else {
				$userId = '0';
			}
		}
		return $userId ? $userId : '0';
	}

	public function getCustomerId() {
		$customer = Mage::getSingleton('customer/session')->getCustomer();
		if (isset($customer)) {
			return Mage::getSingleton('customer/session')->getCustomer()->getId();
		} else {
			return "";
		}
	}

	public function getCustomerEmail() {
		$customer = Mage::getSingleton('customer/session')->getCustomer();
		if (isset($customer)) {
			return Mage::getSingleton('customer/session')->getCustomer()->getEmail();
		} else {
			return "";
		}
	}

	private function getTrackerUrl() {
		if ($this->dev) {
			return "dev-tracker.brainsins.com";
		} else {
			return "tracker.brainsins.com";
		}
	}

	private function getRecommenderUrl() {
		if ($this->dev) {
			return "dev-recommender.brainsins.com";
		} else {
			return "recommender.brainsins.com";
		}
	}

	public function _toHtml() {

		$enabled = Mage::getStoreConfig('brainsins/BS_ENABLED');
		if ($enabled !== '1') {
			return "";
		}

		$trackerUrl = $this->getTrackerUrl();
		$recUrl = $this->getRecommenderUrl();
		$key = Mage::getStoreConfig('brainsins/BSKEY');
		$session = Mage::getSingleton("core/session", array("name" => "frontend"));

		$recScript = "";

		if (!isset($key) || !$key || $key == '') {
			return "";
		}

		$html = "";
		$url = Mage::helper('core/url')->getCurrentUrl();

		$jscriptFilePath = Mage::getBaseDir() . "/js/brainsins/brainsins.js";
		$customCssFilePath = Mage::getBAseDir() . "/js/brainsins/brainsins.css";

		if (file_exists($jscriptFilePath)) {
			$jscriptUrlPath = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS) . "brainsins/brainsins.js";
			$html .= '<script type="text/javascript" src="' . $jscriptUrlPath . '"></script>' . PHP_EOL;
		}

		if (file_exists($customCssFilePath)) {
			$cssUrlPath = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS) . "brainsins/brainsins.css";

			$script = "<script type='text/javascript'>";
			$script .= "var head = document.getElementsByTagName('head')[0];";
			$script .= "var link = document.createElement('link');";
			$script .= "link.rel = 'stylesheet';";
			$script .= "link.type = 'text/css';";
			$script .= "link.href = '" . $cssUrlPath . "';";
			$script .= "link.media = 'all';";
			$script .= "head.appendChild(link);";
			$script .= "</script>";
			$html .= $script;
		}

		//custom js file from config

		$scriptUrl = "";

		if (Mage::app()->getStore()->isCurrentlySecure()) {
			$scriptUrl = Mage::getStoreConfig('brainsins/BS_SCRIPT_THTTPS_URL');
		} else {
			$scriptUrl = Mage::getStoreConfig('brainsins/BS_SCRIPT_URL');
		}

		if (isset($scriptUrl) && $scriptUrl != "") {
			$html .= "<script type='text/javascript' src='" . $scriptUrl . "'></script>";
		}

		$html .= '<script type="text/javascript">';
		$html .= 'var bsHost = (("https:" == document.location.protocol) ? "https://" : "http://");' . PHP_EOL;
		$html .= 'document.write(unescape("%3Cscript src=\'" + bsHost + "' . $trackerUrl . '/bstracker.js\' type=\'text/javascript\'%3E%3C/script%3E"));' . PHP_EOL;
		$html .= "</script>";


		//$html .= '<script type="text/javascript" src="' . $trackerUrl . '"></script>' . PHP_EOL;
		$html .='<script type="text/javascript"> try{ var BrainSINSTracker = BrainSINS.getTracker( "' . $key . '");} catch (err) { }</script>' . PHP_EOL;

		$recommendersSccript = '<script type="text/javascript" src="' . $recUrl . '">';

		$page = Mage::app()->getFrontController()->getRequest()->getRouteName();

		$script = "<script type='text/javascript'>" . PHP_EOL;

		//captured events have set flags

		$loginFlag = $session->getData("recsins_login");
		$logoutFlag = $session->getData("recsins_logout");
		$cartFlag = $session->getData("recsins_cart");
		$checkoutSuccessFlag = $session->getData("recsins_checkout_success");

		$isValidUl = array_key_exists('ul', $_COOKIE) && isset($_COOKIE['ul']) && $_COOKIE['ul'];

		if ($this->getCustomerId() && (!$isValidUl || ($loginFlag && $loginFlag == '1'))) {
			$customer = Mage::getSingleton('customer/session')->getCustomer();
			$isNewUser = Mage::getModel("recsins/recsins")->checkNewUser($customer, $this->getCustomerEmail());

			if (!$isNewUser) {
				$script .= "BrainSINSTracker.trackUserLoggedIn(" . $this->getCustomerId() . ");" . PHP_EOL;
			}
			// if it is a new user, login is performed automatically in the checkNewUser method
		}

		if (!$this->getCustomerId() && $isValidUl) {
			$script .= "if (BrainSINSTracker.isLogged()) { BrainSINSTracker.trackUserLoggedOut();}" . PHP_EOL;
		}

		$userId = $this->getUserId();

		//end captured events

		if ($page == 'cms') {
			$pageId = Mage::getSingleton('cms/page')->getIdentifier();
			$current_page = "cms->" . $pageId;
			$url = Mage::helper('core/url')->getCurrentUrl();

			if ($pageId == 'home') {
				$recScript = $this->getJSRecommendations('brainsins/BS_HOME_RECOMMENDER', 'home_recommendations', $userId, null);
			}
		} else if ($page == 'catalog') {

			$product = Mage::registry('current_product');
			$category = Mage::registry('current_category');

			if (isset($product) && $product) {

				$productId = $product->getId();
				$store = Mage::app()->getStore();
				$url = $product->getUrlModel()->getUrl($product, array('_ignore_category' => true)) . "?___store=" . Mage::app()->getStore()->getCode();

				$recScript = $this->getJSRecommendations('brainsins/BS_PRODUCT_RECOMMENDER', 'product_recommendations', $userId, $productId);
			} else if(isset($category) && $category) {
				$categoryId = $category->getId();
				$recScript = $this->getJSRecommendations('brainsins/BS_CATEGORY_RECOMMENDER', 'category_recommendations', $userId, $categoryId);
			} else {
				$current_page = "into catalog but no product nor category";
			}
		} else if ($page == "checkout" || $page == "gomage_checkout") {
			$request = Mage::app()->getFrontController()->getRequest();

			if ($request->getControllerName() == "cart") {
				$recScript = $this->getJSRecommendations('brainsins/BS_CART_RECOMMENDER', 'cart_recommendations', $userId, null);
			} else if ($request->getControllerName() == "onepage" || $request->getControllerName() == "multishipping") {
				if ($request->getActionName() == "success") {
					$recScript = $this->getJSRecommendations('brainsins/BS_CHECKOUT_RECOMMENDER', 'checkout_recommendations', $userId, null);
				}
			}
		}

		//track page url
		$product = Mage::registry('current_product');
		if (isset($product) && $product) {


			$pid = $product->getId();
			$name = $product->getName();
			$name = str_replace("\r\n", " ", $name);
			$name = str_replace("\n", " ", $name);
			$name = strip_tags($name);
			$description = $product->getShortDescription();
			$description = str_replace("\r\n", " ", $description);
			$description = str_replace("\n", " ", $description);
			$description = strip_tags($description);

			$imageUrl = $product->getImageUrl();
			$price = "";

			$priceModel = $product->getPriceModel();
			if (method_exists($priceModel, "getPrices")) {
				$prices = $priceModel->getPrices($product);
				if (is_array($prices) && isset($prices[0])) {
					$price = $prices[0];
				}
			}

			if ($price === "") {
				if (method_exists($price, "getFinalPrice")) {
					$price = $product->getFinalPrice();
					if (!isset($price) || !$price) {
						$price = $product->getPrice();
					}
				} else {
					$price = $product->getPrice();
				}
			}

			$categoryList = array();
			$categoryIds = $product->getCategoryIds();

			if (isset($categoryIds) && is_array($categoryIds)) {
				foreach ($categoryIds as $catId) {
					$category = Mage::getModel('catalog/category')->load($catId);
					$parents = $category->getParentIds();

					if (!in_array($catId, $categoryList)) {
						if (is_numeric($catId)) {
							$categoryList[] = $catId;
						}
					}

					if (isset($parents) && is_array($parents)) {
						foreach ($parents as $parent) {
							if (is_numeric($parent)) {
								if (!in_array($parent, $categoryList)) {
									$categoryList[] = $parent;
								}
							}
						}
					}
				}
			}

			$categories = "";

			foreach ($categoryList as $category) {
				$categories .= $category . ",";
			}

			$typeId = $product->getTypeId();
			if (isset($typeId) && $typeId == "grouped")  {
				$type = $product->getTypeInstance();
				if (isset($type) && is_object($type)) {
					if (method_exists($type, "getChildrenIds")) {
						$ids = $type->getChildrenIds($product->getId());
						if (isset($ids) && is_array($ids)) {
							foreach($ids as $idsList) {
								if (is_array($idsList)) {
									foreach($idsList as $id) {
										$pid .= "," . $id;
									}
								}
							}
						}
					}
				}
			}

			$script .= 'BrainSINSTracker.trackProductview("' . $pid . '");' . PHP_EOL;
			$script .= "if (typeof BrainSINS != undefined) {" . PHP_EOL;
			$script .= "BrainSINS.BrainsinsProductInfo = Object();" . PHP_EOL;
			$script .= "try{BrainSINS.BrainsinsProductInfo.id = '" . utf8_encode(htmlspecialchars($pid, ENT_QUOTES)) . "';}catch(err){}" . PHP_EOL;
			$script .= "try{BrainSINS.BrainsinsProductInfo.price = '" . utf8_encode(htmlspecialchars($price, ENT_QUOTES)) . "';}catch(err){}" . PHP_EOL;
			$script .= "try{BrainSINS.BrainsinsProductInfo.name = '" . utf8_encode(htmlspecialchars($name, ENT_QUOTES)) . "';}catch(err){}" . PHP_EOL;
			$script .= "try{BrainSINS.BrainsinsProductInfo.description = '" . utf8_encode(htmlspecialchars($description, ENT_QUOTES)) . "';}catch(err){}" . PHP_EOL;
			$script .= "try{BrainSINS.BrainsinsProductInfo.imageUrl = '" . utf8_encode(htmlspecialchars($imageUrl, ENT_QUOTES)) . "';}catch(err){}" . PHP_EOL;
			$script .= "try{BrainSINS.BrainsinsProductInfo.categories = '" . utf8_encode(htmlspecialchars($categories, ENT_QUOTES)) . "';}catch(err){}" . PHP_EOL;
			$script .= "}" . PHP_EOL;

		} else {
			$script .= 'BrainSINSTracker.trackPageview("' . $url . '");' . PHP_EOL;
		}

		if ($cartFlag && $userId) {
			$cart = Mage::helper('checkout/cart')->getCart();
			$quote = $cart->getQuote();
			$recsinsModel = Mage::getModel("recsins/recsins");
			$cartUpload = $recsinsModel->uploadQuotes(array($quote), $userId);
		}

		if ($checkoutSuccessFlag && $userId) {
			$recsinsModel = Mage::getModel("recsins/recsins");
			$lastOrder = Mage::getModel('sales/order')->load(Mage::getSingleton('checkout/session')->getLastOrderId());
			$recsinsModel->trackCheckoutBegin($userId);
			$recsinsModel->trackCheckoutSuccess($lastOrder, $userId);
		}

		// reset flags

		$session->setData("recsins_login", null);
		$session->setData("recsins_logout", null);
		$session->setData("recsins_cart", null);
		$session->setData("recsins_checkout_success", null);


		$script .= "</script>" . PHP_EOL;

		$html .= $script;

		if ($recScript !== "") {
			$html .= $recScript;
		}

		return $html;
	}

	public function getAjaxRequestRecommendations($placeKey, $divId, $userId, $productId) {
		$key = Mage::getStoreConfig('brainsins/BSKEY');

		$recommenderId = Mage::getStoreConfig($placeKey);

		if (!isset($recommenderId) || !$recommenderId) {
			return "";
		}

		if (!isset($productId)) {
			$productId = 0;
		}

		//$prodId = $productId ? "'" . $productId . "'" : 'null';

		$recUrl = $this->getRecommenderUrl();

		$script = "";
		$filter = '';

		if (isset($key) && $key != null && isset($recommenderId)) {

			$paintCallback = "null";
			if ($placeKey == 'brainsins/BS_HOME_RECOMMENDER') {
				$paintCallback = "bsPaintHomeRecommendations";
			} elseif ($placeKey == 'brainsins/BS_PRODUCT_RECOMMENDER') {
				$paintCallback = "bsPaintProductRecommendations";
			} elseif ($placeKey == 'brainsins/BS_CART_RECOMMENDER') {
				$paintCallback = "bsPaintCartRecommendations";
			} elseif ($placeKey == 'brainsins/BS_CHECKOUT_RECOMMENDER') {
				$paintCallback = "bsPaintCheckoutRecommendations";
			} elseif ($placeKey == 'brainsins/BS_CATEGORY_RECOMMENDER') {
				$paintCallback = "bsPaintCategoryRecommendations";
			}
				
			$script .= '<script type="text/javascript">' . PHP_EOL;
			$script .= 'var bsHost = (("https:" == document.location.protocol) ? "https://" : "http://");' . PHP_EOL;
			$script .= 'document.write(unescape("%3Cscript src=\'" + bsHost + "' . $recUrl . '/bsrecwidget.js\' type=\'text/javascript\'%3E%3C/script%3E"));' . PHP_EOL;
			$script .= "</script>" . PHP_EOL;
				
			$script .= '<script type="text/javascript">' . PHP_EOL;
			$script .= 'var BrainSINSRecommender = BrainSINS.getRecommender(BrainSINSTracker);' . PHP_EOL;
			$script .= 'BrainSINSRecommender.loadCSS(' . $recommenderId . ");" . PHP_EOL;
				
			$useHighDetail = Mage::getStoreConfig('brainsins/BS_USE_HIGH_DETAIL');
			if (isset($useHighDetail) && $useHighDetail == "1") {
				$script .= 'BrainSINSRecommender.setDetailsLevel("high");' . PHP_EOL;
			}
				
				
			$currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
				
			$selectedSymbol = Mage::getStoreConfig("brainsins/BS_". $currencyCode . "_SYMBOL");
			$selectedPosition = Mage::getStoreConfig("brainsins/BS_". $currencyCode . "_POSITION");
			$selectedDelimiter = Mage::getStoreConfig("brainsins/BS_". $currencyCode . "_DELIMITER");

			if (isset($selectedSymbol) && $selectedSymbol) {
				$currencySymbol = $selectedSymbol;
			} else {
				$currencySymbol = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();
			}

			$currencyPosition = "curr_symb_right";
			if (isset($selectedPosition) && ($selectedPosition == "curr_symb_left" || $selectedPosition == "curr_symb_right")) {
				$currencyPosition = $selectedPosition;
			}
				
			$currencyDelimiter = ",";
			if (isset($selectedDelimiter) && $selectedDelimiter) {
				$currencyDelimiter = $selectedDelimiter;
			}
				
			if ($currencyPosition == "curr_symb_left") {
				$script .= "BrainSINSRecommender.setCurrencySymbolPosition( BrainSINS.RecommenderConstants.leftPosition );" . PHP_EOL;
			} else {
				$script .= "BrainSINSRecommender.setCurrencySymbolPosition( BrainSINS.RecommenderConstants.rightPosition );" . PHP_EOL;
			}
			$script .= "BrainSINSRecommender.setCurrencyDelimiter( '$currencyDelimiter' );" . PHP_EOL;

				
			$currencyJs = "";
			$langCode = Mage::app()->getStore()->getCode() . $currencyCode;
				
			if ($currencySymbol == "$") {
				$script .= "BrainSINSRecommender.setCurrencySymbol( BrainSINS.RecommenderConstants.dollar );" . PHP_EOL;
			} else if ($currencySymbol == "£") {
				$script .= "BrainSINSRecommender.setCurrencySymbol( BrainSINS.RecommenderConstants.pound );" . PHP_EOL;
			} else if ($currencySymbol == "€") {
				//default behaviour
			} else {
				$script .= "BrainSINSRecommender.setCurrencySymbol('$currencySymbol');" . PHP_EOL;
			}
				
			$script .= "</script>" . PHP_EOL;

			$script .= '<script type="text/javascript">' . PHP_EOL;

			$baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
			$pos = strpos($baseUrl, "/", 8);
			$callUrl = "";
			if ($pos !== false) {
				$callUrl = substr($baseUrl, $pos);
				$callUrl .= "recsins/index/getRecommendations";
			} else {
				$callUrl .= "/recsins/index/getRecommendations";
			}
				
				
			$script .= 'new Ajax.Request("' . $callUrl;
				
			$script .= '/recId/' . $recommenderId;
			$script .= '/userId/' . $userId;
				
			if ($placeKey == 'brainsins/BS_CATEGORY_RECOMMENDER') {
				$script .= '/categories/' . $productId;
				$script .= '/filter/all';
			} elseif ($placeKey == 'brainsins/BS_PRODUCT_RECOMMENDER') {
				$script .= '/prodId/' . $productId;
			}
				
			$script .= '/lang/' . $langCode;
			$script .= '/divName/' . $divId;
				
			$script .= '",{';
			$script .= 'method : "get",' . PHP_EOL;
			$script .= 'onSuccess : function(transport) {' . PHP_EOL;
			$script .= 'if (typeof ' . $paintCallback . ' == "function") {';
			$script .= $paintCallback . "(transport.responseJSON);";
			$script .= "} else {";
			$script .= "BrainSINSRecommender.paintRecommendations(transport.responseJSON);";
			$script .="}";
				
			$script .= '}' . PHP_EOL;
			$script .= '});' . PHP_EOL;

			$script .= "</script>" . PHP_EOL;
		}

		return $script;
	}

	public function getJSRecommendations($placeKey, $divId, $userId, $productId) {

		$useAjax = Mage::getStoreConfig('brainsins/BS_USE_AJAX_REQUESTS');

		if (isset($useAjax) && $useAjax == "1") {
			return $this->getAjaxRequestRecommendations($placeKey, $divId, $userId, $productId);
		}

		$key = Mage::getStoreConfig('brainsins/BSKEY');

		$recommenderId = Mage::getStoreConfig($placeKey);

		if (!isset($recommenderId) || !$recommenderId) {
			return "";
		}

		if (!isset($productId)) {
			$productId = 0;
		}

		$prodId = $productId ? "'" . $productId . "'" : 'null';

		$recUrl = $this->getRecommenderUrl();

		$script = "";
		$filter = '';

		if (isset($key) && $key != null && isset($recommenderId)) {

			$paintCallback = "null";

			$currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
				
			$selectedSymbol = Mage::getStoreConfig("brainsins/BS_". $currencyCode . "_SYMBOL");
			$selectedPosition = Mage::getStoreConfig("brainsins/BS_". $currencyCode . "_POSITION");
			$selectedDelimiter = Mage::getStoreConfig("brainsins/BS_". $currencyCode . "_DELIMITER");
			if (isset($selectedSymbol) && $selectedSymbol) {
				$currencySymbol = $selectedSymbol;
			} else {
				$currencySymbol = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();
			}

			$currencyPosition = "curr_symb_right";
			if (isset($selectedPosition) && ($selectedPosition == "curr_symb_left" || $selectedPosition == "curr_symb_right")) {
				$currencyPosition = $selectedPosition;
			}
				
			$currencyDelimiter = ",";
			if (isset($selectedDelimiter) && $selectedDelimiter) {
				$currencyDelimiter = $selectedDelimiter;
			}
			
			$currencyJs = "";
				
				
			
			if ($currencyPosition == "curr_symb_left") {
				$currencyJs .= "BrainSINSRecommender.setCurrencySymbolPosition( BrainSINS.RecommenderConstants.leftPosition );" . PHP_EOL;
			} else {
				$currencyJs .= "BrainSINSRecommender.setCurrencySymbolPosition( BrainSINS.RecommenderConstants.rightPosition );" . PHP_EOL;
			}
			$currencyJs .= "BrainSINSRecommender.setCurrencyDelimiter( '$currencyDelimiter' );" . PHP_EOL;

				
			$langCode = Mage::app()->getStore()->getCode() . $currencyCode;
				
			if ($currencySymbol == "$") {
				$currencyJs .= "BrainSINSRecommender.setCurrencySymbol( BrainSINS.RecommenderConstants.dollar );" . PHP_EOL;
			} else if ($currencySymbol == "£") {
				$currencyJs .= "BrainSINSRecommender.setCurrencySymbol( BrainSINS.RecommenderConstants.pound );" . PHP_EOL;
			} else if ($currencySymbol == "€") {
				//default behaviour
			} else {
				$currencyJs .= "BrainSINSRecommender.setCurrencySymbol('$currencySymbol');" . PHP_EOL;
			}				
				
			if ($placeKey == 'brainsins/BS_HOME_RECOMMENDER') {
				$paintCallback = "typeof bsPaintHomeRecommendations == 'undefined' ? null : bsPaintHomeRecommendations";
			} elseif ($placeKey == 'brainsins/BS_PRODUCT_RECOMMENDER') {
				$paintCallback = "typeof bsPaintProductRecommendations == 'undefined' ? null : bsPaintProductRecommendations";
			} elseif ($placeKey == 'brainsins/BS_CART_RECOMMENDER') {
				$paintCallback = "typeof bsPaintCartRecommendations == 'undefined' ? null : bsPaintCartRecommendations";
			} elseif ($placeKey == 'brainsins/BS_CHECKOUT_RECOMMENDER') {
				$paintCallback = "typeof bsPaintCheckoutRecommendations == 'undefined' ? null : bsPaintCheckoutRecommendations";
			} elseif ($placeKey == 'brainsins/BS_CATEGORY_RECOMMENDER') {
				$paintCallback = "typeof bsPaintCategoryRecommendations == 'undefined' ? null : bsPaintCategoryRecommendations";
				//category id is passed in the product id parameter
				$categoryId = $productId;
				$filter = 'BrainSINSRecommender.setFilterCategories( "' . $categoryId . '" );' . PHP_EOL;
				$filter .= 'BrainSINSRecommender.setFilter(BrainSINS.RecommenderConstants.all);';
			}

			$script .= '<script type="text/javascript">' . PHP_EOL;
			$script .= 'var bsHost = (("https:" == document.location.protocol) ? "https://" : "http://");' . PHP_EOL;
			$script .= 'document.write(unescape("%3Cscript src=\'" + bsHost + "' . $recUrl . '/bsrecwidget.js\' type=\'text/javascript\'%3E%3C/script%3E"));' . PHP_EOL;
			$script .= "</script>" . PHP_EOL;

			$useHighDetailScript = "";

			$useHighDetail = Mage::getStoreConfig('brainsins/BS_USE_HIGH_DETAIL');
			if (isset($useHighDetail) && $useHighDetail == "1") {
				$useHighDetailScript = 'BrainSINSRecommender.setDetailsLevel("high");' . PHP_EOL;
			}

			$script .= '
			<script type="text/javascript">
			try{
			BrainSINSTracker.setCustomAttribute("currencySymbol", "' . $currencySymbol . '");
			var BrainSINSRecommender = BrainSINS.getRecommender( BrainSINSTracker );
			' . $filter . '
			' . $currencyJs . '
			' . $useHighDetailScript .'
			BrainSINSRecommender.loadWidget("' . $recommenderId . '",' . $prodId . ',"' . $langCode . '","' . $divId . '", null,' . $paintCallback . ');
		}catch(err) { }
		</script>
		';
		}

		return $script;
	}
}
