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
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

class Brainsins_Recommender_FeedController extends Mage_Core_Controller_Front_Action
{

    private $_store;

    private $_status;
    private $_statusComparator;
    private $_visibility;
    private $_visibilityComparator;

    private $_numPage;
    private $_pageSize;

    private $_oos;
    private $_tax;
    private $_width;
    private $_height;
    private $_special;
    private $_debug;
    private $_pretty;

    private $_attrs;
    private $_currencies;
    private $_baseCurrency;

    private function _loadParam($name, $csv = false)
    {

        $value = $this->getRequest()->getParam($name);
        if ($value !== null && $csv) {
            $value = preg_split("/,/", $value);
        }

        return $value;
    }

    private function _getConfigParam($store, $path, $provided)
    {
        if ($provided !== null) {
            return $provided;
        } else {
            return Mage::getStoreConfig("brainsins_recommender_options/$path", $store);
        }
    }

    public function configure()
    {
        $this->_store = $this->getRequest()->getParam('store');
        $this->_status = $this->getRequest()->getParam('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $this->_statusComparator = $this->getRequest()->getParam('statusComparator', 'gteq');
        $this->_visibility = $this->getRequest()->getParam('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);
        $this->_visibilityComparator = $this->getRequest()->getParam('visibilityComparator', 'gteq');

        $this->_numPage = $this->getRequest()->getParam('page');
        $this->_pageSize = $this->getRequest()->getParam('size');

        // config override
        $oosParam = $this->getRequest()->getParam('oos');
        $taxParam = $this->getRequest()->getParam('tax');
        $widthParam = $this->getRequest()->getParam('img_width');
        $heightParam = $this->getRequest()->getParam('img_height');
        $specialParam = $this->getRequest()->getParam('special');

        $this->_oos = $this->_getConfigParam($this->_store, "brainsins_recommender_feed/include_oos_products", $oosParam);
        $this->_tax = $this->_getConfigParam($this->_store, "brainsins_recommender_feed/tax_included", $taxParam);
        $this->_width = $this->_getConfigParam($this->_store, "brainsins_recommender_feed/product_image_resize_width", $widthParam);
        $this->_height = $this->_getConfigParam($this->_store, "brainsins_recommender_feed/product_image_resize_height", $heightParam);
        $this->_special = $this->_getConfigParam($this->_store, "brainsins_recommender_feed/special_price", $specialParam);

        $attrParam = $this->getRequest()->getParam('attrs');
        $extraAttrParam = $this->getRequest()->getParam('extraAttrs');
        $currenciesParam = $this->getRequest()->getParam('currencies');

        $this->_debug = $this->getRequest()->getParam('debug', false);
        $this->_pretty = $this->getRequest()->getParam('pretty', false);

        $this->_attrs = Array();
        if ($attrParam !== null) {
            $this->_attrs = preg_split("/,/", $attrParam);
        } else {
            $this->_attrs = Array("entity_id", "sku", "name", "image", "small_image", "price",
                "special_price", "special_from_date", "special_to_date", "regular_price", "final_price", "tax_class_id", "is_in_stock", "is_salable",
                "type_id",
                "entity_type_id", "status", "url_key", "visibility", "url_path", "minimal_price", "min_price"
            );
        }

        if ($extraAttrParam) {
            $this->_attrs = array_merge($this->_attrs, preg_split("/,/", $extraAttrParam));
        }

        if ($this->_debug) {
            $this->attrs = Array("*");
        }

        $this->_currencies = Array();
        if ($currenciesParam) {
            $this->_currencies = preg_split("/,/", $currenciesParam);
        }

        if ($this->_store) {
            Mage::app()->setCurrentStore($this->_store);
        }

        $this->_baseCurrency = Mage::app()->getStore($this->_store)->getBaseCurrency()->getCode();

    }

    public function productCountAction()
    {
        $this->configure();
        $result = Array();

        $result ["count"] = Mage::getModel('catalog/product')
            ->getCollection()
            ->addAttributeToSelect('entity_id')
            ->addFieldToFilter('status', Array($this->_statusComparator => $this->_status))
            ->addFieldToFilter('visibility', Array($this->_visibilityComparator => $this->_visibility))
            ->getSize();

        $output = json_encode($result);
        $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody($output);
    }

    public function productsAction()
    {
        $this->configure();

        // load collection
        $products = Mage::getModel('catalog/product')
            ->getCollection();

        if ($this->_store) {
            $products->setStore($this->_store);
        }

        foreach ($this->_attrs as $attr) {
            $products->addAttributeToSelect($attr);
        }

        $products
            ->addFieldToFilter('status', Array($this->_statusComparator => $this->_status))
            ->addFieldToFilter('visibility', Array($this->_visibilityComparator => $this->_visibility))
            ->addAttributeToSort('entity_id', 'asc')
            ->setPage($this->_numPage, $this->_pageSize);

        $result = Array();
        foreach ($products as $product) {
            $product_info = $this->_getProduct($product);
            if ($product_info) {
                $result [] = $product_info;
            }
        }

        $outputOptions = 0;
        if ($this->_pretty) {
            $outputOptions = JSON_PRETTY_PRINT;
        }

        $output = json_encode($result, $outputOptions);

        $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody($output);
    }

    public function productAction()
    {
        $this->configure();

        $id = $this->getRequest()->getParam('id');

        $product = Mage::getModel("catalog/product")->load($id);

        $result[$id] = $this->_getProduct($product);
        $output = json_encode($result);
        $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody($output);
    }

    public function currenciesAction()
    {

        $stores = Mage::app()->getStore()->getCollection();
        $result = Array();
        foreach ($stores as $store) {
            $code = $store->getCode();
            $id = $store->getId();
            $result[$id] = Array();
            $currencies = $store->getAvailableCurrencyCodes(true);
            foreach ($currencies as $currency) {
                $result[$id][] = $currency;
            }
        }

        $output = json_encode($result);
        $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody($output);
    }

    public function attributesAction()
    {
        $ids = Mage::getResourceModel('eav/entity_attribute_collection')->getAllIds();

        $attributes = Array();

        foreach ($ids as $id) {
            $attributeInfo = Mage::getModel('catalog/resource_eav_attribute')->load($id);
            $options = $attributeInfo->getSource()->getAllOptions(false);
            $attribute = Array();
            $attribute['id'] = $id;
            $attribute['label'] = $attributeInfo->getData("attribute_code");
            $attribute['options'] = $options;
            $attributes[] = $attribute;
        }

        $output = json_encode($attributes);
        $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody($output);
    }

    public function attributeAction()
    {
        $id = $this->getRequest()->getParam('id');
        $name = $this->getRequest()->getParam('name');

        $attributeInfo = null;

        if ($name != null && $name != "") {
            $attributeInfo = Mage::getResourceModel('eav/entity_attribute_collection')->setCodeFilter($name)->getFirstItem();
            $id = $attributeInfo->getAttributeId();
        } else {
            $attributeInfo = Mage::getResourceModel('eav/entity_attribute_collection')->getItemById($id);
        }

        // $attributeInfo = Mage::getResourceModel('eav/entity_attribute_collection')->getItemById($id);

        $options = $this->_getAttributeOptions($id);
        $attribute = Array();
        $attribute['id'] = $id;
        $attribute['label'] = $attributeInfo->getData("attribute_code");
        $attribute['options'] = $options;
        $attributes[] = $attribute;

        $output = json_encode($attributes);
        $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody($output);

    }

    public function storesAction()
    {
        $result = $this->_getStoreList();

        $output = json_encode($result);
        $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody($output);
    }

    public function categoriesAction()
    {
        $result = array();
        $allCats = Mage::getModel('catalog/category')->getCollection();
        $storeList = $this->_getStoreList();
        foreach ($allCats as $category) {
            $category->load($category->getId());
            $catInfo = Array();

            $catInfo ["id"] = $category->getId();

            $catInfo ["parent_id"] = $category->getParentId();

            $catInfo ["names"] = Array();

            foreach ($category->getStoreIds() as $storeId) {
                if (!$storeId) {
                    continue;
                }
                $storeCat = Mage::getModel('catalog/category')->setStore($storeId)->load($category->getId());
                $storeCode = $storeList [$storeId];
                $catInfo ["names"] [$storeCode] = $storeCat->getName();
            }

            $result [$category->getId()] = $catInfo;
        }
        $output = json_encode($result);
        $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody($output);
    }

    public function configAction() {
        $result = Array();
        $idAttr = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_advanced/product_id_field', Mage::app()->getStore()->getStoreId());

        $result["idAttr"] = $idAttr;

        $output = json_encode($result);
        $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody($output);
    }


    private function _getStoreList()
    {
        $stores = Mage::app()->getStore()->getCollection();
        $result = array();
        foreach ($stores as $store) {
            $result [$store->getId()] = $store->getCode();
        }
        return $result;
    }

    private function _loadProductBasics($product, &$result)
    {
        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getIsInStock();
        $url = preg_replace("/[\?&]?SID=[^\?&]*/", "", $product->getProductUrl());
        //$product->
        $imageUrl = Mage::helper('brainsins_recommender')->getProductImage($product, $this->_width, $this->_height);

        $result["bs_stock"] = $stock;
        $result["bs_url"] = $url;
        $result["bs_image_url"] = $imageUrl;

        $result["bs_categories"] = $product->getCategoryIds();

        $configurable_product_model = Mage::getModel('catalog/product_type_configurable');
        $parentIdArray = $configurable_product_model->getParentIdsByChild($product->getId());
        $parentId = "";
        if (count($parentIdArray) > 0) {
            $parentId = $parentIdArray[0];
        }

        $result["bs_parent_id"] = $parentId;
    }

    private function _loadProductDebug($product, &$result)
    {
        foreach ($product->getAttributes() as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            $info [$attributeCode] = $product->getData($attributeCode);
            $result ["debug"] = $info;
        }
    }

    private function _loadProductPrices($product, &$result)
    {
        $finalPrice = $product->getFinalPrice();


        $result["bs_final_price"] = $finalPrice;
        $result["bs_final_price_converted"] = $this->_priceForTracking($product, $finalPrice);
        $result["bs_price"] = $product->getPrice();
        $result["bs_price_converted"] = $this->_priceForTracking($product, $product->getPrice());
        $result["bs_price_computed"] = $this->_getProductPrice($product);
        $result["bs_price_computed_converted"] = $this->_toBaseCurrency($result["bs_price_computed"]);
        $result["bs_price_tax"] = $this->_priceForTracking($product, $this->_getProductPrice($product));

        $result["prices"] = Array();

        foreach ($this->_currencies as $curr) {

            try {
                $targetCurrency = $curr;

                if ($this->_baseCurrency == $targetCurrency) {
                    $result ["prices"] [$targetCurrency] = $finalPrice;
                    continue;
                }

                $price_converted = number_format(Mage::helper('directory')
                    ->currencyConvert($finalPrice, $this->_baseCurrency, $targetCurrency), 2, '.', '');
                $result ["prices"] [$curr] = $price_converted;
            } catch (Exception $e) {
            }
        }
    }

    private function _getProduct($product)
    {
        $result = Array();

        if ($this->_debug) {
            $this->_loadProductDebug($product, $result);
        }

        if ($this->_attrs) {
            foreach ($this->_attrs as $attr) {
                $result[$attr] = $product->getData($attr);
            }
        }

        $this->_loadProductBasics($product, $result);
        $this->_loadProductPrices($product, $result);

        return $result;
    }

    private function _getProductPrice($product)
    {
        $product_price = 0.0;

        if ($this->_tax == '1' && $this->_special == '1') {
            $product_price = Mage::helper('tax')->getPrice($product, $product->getFinalPrice(), true, null, null, null, null, false);
        } elseif ($this->_tax == '1' && $this->_special == '0') {
            $product_price = Mage::helper('tax')->getPrice($product, $product->getPrice(), true, null, null, null, null, false);
        }

        if ($this->_tax == '0' && $this->_special == '1') {
            $product_price = Mage::helper('tax')->getPrice($product, $product->getFinalPrice(), true, null, null, null, null, true);
        } elseif ($this->_tax == '0' && $this->_special == '0') {
            $product_price = Mage::helper('tax')->getPrice($product, $product->getPrice(), true, null, null, null, null, true);
        }

        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            if ($this->_tax == '1' && $this->_special == '1') {
                $product_price = Mage::helper('tax')->getPrice($product, Mage::helper('brainsins_recommender')
                    ->getBundlePrice($product, 'min', 1), true, null, null, null, null, false);
            } elseif ($this->_tax == '1' && $this->_special == '0') {
                $product_price = Mage::helper('tax')->getPrice($product, Mage::helper('brainsins_recommender')
                    ->getBundlePrice($product, 'min', 1), true, null, null, null, null, false);
            }
            if ($this->_tax == '0' && $this->_special == '1') {
                $product_price = Mage::helper('tax')->getPrice($product, Mage::helper('brainsins_recommender')
                    ->getBundlePrice($product, 'min', 0), true, null, null, null, null, true);
            } elseif ($this->_tax == '0' && $this->_special == '0') {
                $product_price = Mage::helper('tax')->getPrice($product, Mage::helper('brainsins_recommender')
                    ->getBundlePrice($product, 'min', 0), true, null, null, null, null, true);
            }
        }

        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            $product_final_price = Mage::helper('brainsins_recommender')->getBundlePrice($product, 'min', 1);
            $product_regular_price = Mage::helper('brainsins_recommender')->getBundlePrice($product, 'min', 1);
        } else {
            $product_final_price = $product->getFinalPrice();
            $product_regular_price = $product->getPrice();
        }

        return number_format($product_price, 2, '.', '');
    }

    private function _getAttributeOptions($id)
    {
        $attribute = Mage::getModel('catalog/resource_eav_attribute')->load($id);
        $attributeOptions = $attribute->getSource()->getAllOptions(false);
        return $attributeOptions;
    }

    private function _getAttributeInfo($id)
    {

    }

    private function _toBaseCurrency($price)
    {
        /*
        $currentCurrencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
        $baseCurrencyCode = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_general/base_currency', Mage::app()->getStore()->getStoreId());

        if ($baseCurrencyCode == "") {
            $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
        }

        if ($baseCurrencyCode == $currentCurrencyCode) {
            return $price;
        } else {
            return number_format(Mage::helper('directory')
                ->currencyConvert($price, $currentCurrencyCode, $baseCurrencyCode), 2, '.', '');
        }
        */

        $currentCurrencyCode = null;

        $currentCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();

        $baseCurrencyCode = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_general/base_currency', Mage::app()->getStore()->getStoreId());

        if ($baseCurrencyCode == "") {
            $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
        }

        if ($baseCurrencyCode == $currentCurrencyCode) {
            return $price;
        } else {

            $allowedCurrencies = Mage::getModel('directory/currency')->getConfigAllowCurrencies();
            if (in_array($baseCurrencyCode, $allowedCurrencies)) {
                $rates1 = Mage::getModel('directory/currency')->getCurrencyRates($baseCurrencyCode, array_values($allowedCurrencies));
                $rates2 = Mage::getModel('directory/currency')->getCurrencyRates($currentCurrencyCode, array_values($allowedCurrencies));

                if (array_key_exists($baseCurrencyCode, $rates1)) {
                    return round($price / $rates1[$currentCurrencyCode], 2);
                } else if (array_key_exists($baseCurrencyCode, $rates2)) {
                    return round($price * $rates2[$currentCurrencyCode], 2);
                } else {
                    return $price;
                }

            } else {
                return $price;
            }
            //return number_format(Mage::helper('directory')
            //->currencyConvert($price, $currentCurrencyCode, $baseCurrencyCode), 2, '.', '');

        }
    }

    private function _configuredTaxPrice($product, $price)
    {
        $config = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_advanced/tracking_tax', Mage::app()->getStore()->getStoreId());
        if ($config == "1") {
            return Mage::helper('tax')->getPrice($product, $price, true);
        } else {
            return Mage::helper('tax')->getPrice($product, $price, false);
        }
    }

    private function _priceForTracking($product, $price)
    {
        return $this->_toBaseCurrency($this->_configuredTaxPrice($product, $price));
    }
}