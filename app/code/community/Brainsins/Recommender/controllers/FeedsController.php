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
error_reporting(E_ALL);
ini_set('display_errors', 1);

class Brainsins_Recommender_FeedsController extends Mage_Core_Controller_Front_Action
{
    public function productsAction()
    {
        $key_param = $this->getRequest()->getParam('key');
        if (!$key_param || !isset ($key_param)) {
            echo '[INVALID PARAMETERS]';
            die ();
        }
        $feed = Mage::helper('brainsins_recommender')->getProductsFeed($key_param);
        $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/xml')->setBody($feed);
    }

    public function productCountAction()
    {
        $result = Array();
        $result ["count"] = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('entity_id')->addFieldToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)->addFieldToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)->getSize();
        $output = json_encode($result);
        $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody($output);
    }

    public function productListAction()
    {
        $numPage = $this->getRequest()->getParam('numPage');
        $pageSize = $this->getRequest()->getParam('pageSize');
        $debug = $this->getRequest()->getParam('debug');
        $oosParam = $this->getRequest()->getParam('oos');
        $width = $this->getRequest()->getParam('img_width');
        $height = $this->getRequest()->getParam('img_height');

        $products = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('entity_id')->addFieldToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)->addFieldToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)->addAttributeToSort('entity_id', 'asc')->setPage($numPage, $pageSize);

        $include_oos_products = false;

        if ($oosParam === null) {
            $include_oos_products = true && Mage::getStoreConfig('brainsins_recommender_options/product_feed/include_oos_products', Mage::app()->getStore()->getStoreId());
        } else {
            $include_oos_products = $oosParam && true;
        }

        $result = Array();
        foreach ($products as $product) {
            $id = $product->getId();
            $product_info = $this->_getProduct($id, $debug, $include_oos_products, $width, $height);
            if ($product_info) {
                $result [] = $product_info;
            }
        }

        $output = json_encode($result);
        $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody($output);
    }

    private function _getProduct($productId, $debug = false, $include_oos_products = false, $width = false, $height = false)
    {
        $tax_included = Mage::getStoreConfig('brainsins_recommender_options/product_feed/tax_included', Mage::app()->getStore()->getStoreId());
        $special_price = Mage::getStoreConfig('brainsins_recommender_options/product_feed/special_price', Mage::app()->getStore()->getStoreId());

        $storeList = $this->_getStoreList();
        $currencies = $this->_getCurrencyCodes();
        $product = Mage::getModel('catalog/product');
        $product->load($productId);

        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getIsInStock();

        // check product stock
        if (!$include_oos_products && !$stock) {
            return null;
        }

        $result = Array();

        // just to have id as first field
        $result ["id"] = "";

        $result ["stock"] = $stock ? "1" : "0";

        // define multiproperties
        $result ["names"] = Array();
        $result ["urls"] = Array();
        $result ["prices"] = Array();

        foreach ($product->getStoreIds() as $storeId) {
            $storeProduct = Mage::getModel('catalog/product');
            $storeProduct->setStoreId($storeId);
            $storeProduct->load($productId);
            $storeCode = $storeList [$storeId];
            // NAMES -----
            $result ["names"] [$storeCode] = $storeProduct->getName();
            $result ["urls"] [$storeCode] = preg_replace("/[\?&]?___store=[^\?&]*/", "", $storeProduct->getProductUrl());
        }

        // IMAGE URL -----
        $imageUrl = Mage::helper('brainsins_recommender')->getProductImage($product, $width, $height);

        // $image_width = Mage::getStoreConfig('brainsins_recommender_options/product_feed/product_image_resize_width', Mage::app()->getStore()->getStoreId());
        // $image_height = Mage::getStoreConfig('brainsins_recommender_options/product_feed/product_image_resize_height', Mage::app()->getStore()->getStoreId());

        // if (!$image_width || $image_width == "" || $image_width == "0" || !is_numeric($image_width)) {
        // $image_width = null;
        // }

        // if (!$image_height || $image_height == "" || $image_height == "0" || !is_numeric($image_height)) {
        // $image_height = null;
        // }

        // if (!$image_width && !$image_height) {
        // $imageUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product'.$product->getImage();
        // } else {
        // $imageUrl = (string) Mage::helper('catalog/image')->init($product, "image")->resize($image_width, $image_height);
        // }

        $result ["imageUrl"] = $imageUrl;

        // URL ---

        $url = preg_replace("/[\?&]?___store=[^\?&]*/", "", $product->getProductUrl());
        $result ["url"] = $url;

        // PRICES -----

        $product_price = 0.0;

        if ($tax_included == '1' && $special_price == '1') {
            $product_price = Mage::helper('tax')->getPrice($product, $product->getFinalPrice(), true, null, null, null, null, false);
        } elseif ($tax_included == '1' && $special_price == '0') {
            $product_price = Mage::helper('tax')->getPrice($product, $product->getPrice(), true, null, null, null, null, false);
        }

        if ($tax_included == '0' && $special_price == '1') {
            $product_price = Mage::helper('tax')->getPrice($product, $product->getFinalPrice(), true, null, null, null, null, true);
        } elseif ($tax_included == '0' && $special_price == '0') {
            $product_price = Mage::helper('tax')->getPrice($product, $product->getPrice(), true, null, null, null, null, true);
        }

        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            if ($tax_included == '1' && $special_price == '1') {
                $product_price = Mage::helper('tax')->getPrice($product, Mage::helper('brainsins_recommender')->getBundlePrice($product, 'min', 1), true, null, null, null, null, false);
            } elseif ($tax_included == '1' && $special_price == '0') {
                $product_price = Mage::helper('tax')->getPrice($product, Mage::helper('brainsins_recommender')->getBundlePrice($product, 'min', 1), true, null, null, null, null, false);
            }
            if ($tax_included == '0' && $special_price == '1') {
                $product_price = Mage::helper('tax')->getPrice($product, Mage::helper('brainsins_recommender')->getBundlePrice($product, 'min', 0), true, null, null, null, null, true);
            } elseif ($tax_included == '0' && $special_price == '0') {
                $product_price = Mage::helper('tax')->getPrice($product, Mage::helper('brainsins_recommender')->getBundlePrice($product, 'min', 0), true, null, null, null, null, true);
            }
        }

        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            $product_final_price = Mage::helper('brainsins_recommender')->getBundlePrice($product, 'min', 1);
            $product_regular_price = Mage::helper('brainsins_recommender')->getBundlePrice($product, 'min', 1);
        } else {
            $product_final_price = $product->getFinalPrice();
            $product_regular_price = $product->getPrice();
        }

        $result ["price"] = number_format($product_price, 2, '.', '');
        $baseCurrency = Mage::app()->getStore()->getBaseCurrencyCode();

        foreach ($currencies as $curr) {

            try {
                $targetCurrency = Mage::getModel('directory/currency')->load($curr);
                if ($targetCurrency) {
                    $targetcurrency = $targetCurrency->getCode();
                } else {
                    continue;
                }

                if ($baseCurrency == $targetCurrency) {
                    $result ["prices"] [$targetCurrency] = $product_final_price;
                    continue;
                }

                $price_converted = number_format(Mage::helper('directory')->currencyConvert($product_final_price, $baseCurrency, $targetCurrency), 2, '.', '');
                $result ["prices"] [$curr] = $price_converted;
            } catch (Exception $e) {
            }
        }

        // easy stuff
        $result ["id"] = $product->getId();
        $result ["sku"] = $product->getSku();
        $result ["categories"] = $product->getCategoryIds();

        if ($debug) {
            $info = Array();
            foreach ($product->getAttributes() as $attribute) {
                $attributeCode = $attribute->getAttributeCode();
                $info [$attributeCode] = $product->getData($attributeCode);
            }
            $result ["debug"] = $info;
        }

        return $result;
    }

    public function productAction()
    {
        $id = $this->getRequest()->getParam('id');
        $debug = $this->getRequest()->getParam('debug');
        $oosParam = $this->getREquest()->getParam('oos');

        $include_oos_products = false;

        if ($oosParam === null) {
            $include_oos_products = true && Mage::getStoreConfig('brainsins_recommender_options/product_feed/include_oos_products', Mage::app()->getStore()->getStoreId());
        } else {
            $include_oos_products = $oosParam && true;
        }

        $result = $this->_getProduct($id, $debug, $include_oos_products);
        $output = json_encode($result);
        $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody($output);
    }

    private function _getCurrencyCodes()
    {
        $stores = Mage::app()->getStore()->getCollection();
        $result = array();
        foreach ($stores as $store) {
            $currencies = $store->getAvailableCurrencyCodes(true);
            foreach ($currencies as $currency) {
                if (!in_array($currency, $result)) {
                    $result [] = $currency;
                }
            }
        }

        return $result;
    }

    private function _getAttributeOptions($id) {
        $attribute = Mage::getModel('catalog/resource_eav_attribute')->load($id);
        $attributeOptions = $attribute ->getSource()->getAllOptions(false);
        return $attributeOptions;
    }

    private function _getAttributeInfo($id) {

    }

    public function attributeListAction()
    {
        $ids = Mage::getResourceModel('eav/entity_attribute_collection')->getAllIds();

        $attributes = Array();

        foreach($ids as $id) {
            $attributeInfo = Mage::getModel('catalog/resource_eav_attribute')->load($id);
            $options = $attributeInfo ->getSource()->getAllOptions(false);
            $attribute = Array();
            $attribute['id'] = $id;
            $attribute['label'] = $attributeInfo->getData("attribute_code");
            $attribute['options'] = $options;
            $attributes[]=$attribute;
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
        $attributes[]=$attribute;

        $output = json_encode($attributes);
        $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody($output);

    }

    public function currencyListAction()
    {
        $result = $this->_getCurrencyCodes();

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

    public function storeListAction()
    {
        $result = $this->_getStoreList();

        $output = json_encode($result);
        $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody($output);
    }

    public function categoriesAction()
    {
        $result = array();
        $allcats = Mage::getModel('catalog/category')->getCollection();
        $storeList = $this->_getStoreList();
        foreach ($allcats as $category) {
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

                $storeCatInfo = Array();
                $storeCode = $storeList [$storeId];
                $catInfo ["names"] [$storeCode] = $storeCat->getName();
            }

            $result [$category->getId()] = $catInfo;
        }
        $output = json_encode($result);
        $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody($output);
    }

    // getParentId()
    public function categoryTreeAction()
    {
        $categories = Mage::getModel('catalog/category')->getCollection()->addAttributeToSelect('*')->addAttributeToFilter('level', 2);

        $result = Array();

        foreach ($categories as $category) {
            $id = $category->getId();
            $name = $category->getName();
        }

        $output = json_encode($result);
        $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody($output);
    }
}