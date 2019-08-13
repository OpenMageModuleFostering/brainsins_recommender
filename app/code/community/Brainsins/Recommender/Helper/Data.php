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

    public static function getApiUrl()
    {
        return 'http://api.brainsins.com/RecSinsAPI/api/';

    }

    public function configuredTaxPrice(Mage_Sales_Model_Quote_Item  $cartItem) {
        $config = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_advanced/tracking_tax', Mage::app()->getStore()->getStoreId());
        $price = $cartItem->getPrice();
        //echo("<h1>xxx $config xxx </h1>");
        if ($config == "1") {
            return Mage::helper('tax')->getPrice($cartItem->getProduct(), $price, true);
        } else {
            return Mage::helper('tax')->getPrice($cartItem->getProduct(), $price, false);
        }
    }

    public function priceForTracking(Mage_Sales_Model_Quote_Item $cartItem)
    {
        // product price is in catalog base currency, so we use base currency instead
        // of current currency
        return $this->toBaseCurrency($this->configuredTaxPrice($cartItem), true);
    }

    public function displayPriceForTracking(Mage_Sales_Model_Quote_Item $cartItem)
    {
        return $cartItem->getPriceInclTax();
    }

    public function productIdForTracking($product) {
        $idAttr = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_advanced/product_id_field', Mage::app()->getStore()->getStoreId());

        if ($product->hasData($idAttr)) {
            return $product->getData($idAttr);
        } else {
            return $product->getId();
        }
    }

    public function categoryIdForTracking($category) {
        $idAttr = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_advanced/category_id_field', Mage::app()->getStore()->getStoreId());

        if ($category->hasData($idAttr)) {
            return $category->getData($idAttr);
        } else {
            return $category->getId();
        }
    }

    public function toBaseCurrency($price, $useBaseCurrencyCode = false)
    {
        $currentCurrencyCode = null;

        if ($useBaseCurrencyCode) {
            $currentCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
        } else {
            $currentCurrencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
        }

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
                    return round($price/$rates1[$currentCurrencyCode], 2);
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

    public function processItem(Mage_Sales_Model_Quote_Item $item)
    {
        $configurable_tracking = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_advanced/configurable_tracking', Mage::app()->getStore()->getStoreId());

        $type = $item->getProductType();
        $parent = $item->getParentItem();
        $id = null;
        $price = null;
        $displayPrice = null;
        $qty = null;

        if ($configurable_tracking == "0") {
            //track simple products
            if ($parent && $parent->getProductType() == "configurable") {
                $id = $item->getProductId();
                $id = $this->productIdForTracking($item->getProduct());
                $qty = $parent->getQty();
                $price = $this->priceForTracking($parent);
                $displayPrice = $this->displayPriceForTracking($parent);
            } else if (!$parent && $type != "configurable") {
                $id = $item->getProductId();
                $qty = $item->getQty();
                $price = $this->priceForTracking($item);
                $displayPrice = $this->displayPriceForTracking($item);
            }
        } else {
            //track configurable products
            if ($type == "simple" && $parent && $parent->GetProductType() == "configurable") {
                return null;
            }

            $id = $item->getProductId();
            $qty = $item->getQty();
            $price = $this->priceForTracking($item);
            $displayPrice = $this->displayPriceForTracking($item);
        }

        if ($id) {
            $product = Array();
            $product["id"] = $id;
            $product["quantity"] = $qty;
            $product["type"] = $type;
            $product["parentId"] = $parent ? $this->productIdForTracking($parent->getProduct()) : null;

            $product["price"] = $price;
            $product["displayPrice"] = $displayPrice;

            return $product;
        }

        return null;
    }

    public function processQuote(Mage_Sales_Model_Quote $quote)
    {
        $configurable_tracking = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_advanced/configurable_tracking', Mage::app()->getStore()->getStoreId());
        $taxConfig = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_advanced/tracking_tax', Mage::app()->getStore()->getStoreId());

        $cart = Array();
        $cart["products"] = Array();

        $items = $quote->getAllItems();

        $totals = $quote->getTotals();

        $tax = 0;
        $cart["totals"] = Array();

        foreach($totals as $total) {

            $code = $total->getCode();
            $value = $total->getValue();

            $cart["totals"][$code] = $this->toBaseCurrency($value);

            if ($code == "tax") {
                $tax = $value;
            } else if ($code == "discount") {
                $cart["discount"] = - $value;
            }
        }

        if ($taxConfig == "1") {
            $cart["totalAmount"] = $this->toBaseCurrency($quote->getGrandTotal());
        } else {
            $cart["totalAmount"] = $this->toBaseCurrency($quote->getGrandTotal() - $tax);
        }

        if (count($items) > 0) {

            for ($i = 0; $i < sizeof($items); $i++) {

                $item = $items[$i];
                $product = $this->processItem($item);
                if ($product) {
                    $cart["products"][] = $product;
                }

                /*$type = $item->getProductType();
                $parent = $item->getParentItem();

                if ($configurable_tracking == "1") {

                } else {

                }

                if ($parent) {

                    if ($parent->getProductType() == "bundle") {
                        continue;
                    } else if ($parent->getProductType() == "configurable") {
                        $product = Array();
                        $product["id"] = $item->getProductId();
                        $product["quantity"] = $item->getQty();
                        $product["type"] = $type;
                        $product["parent"] = $parent;

                        $product["price"] = $this->priceForTracking($parent);
                        $product["displayPrice"] = $this->displayPriceForTracking($parent);


                        $cart["products"][] = $product;
                    }

                } else {

                    if ($type == "configurable") {
                        continue;
                    }

                    $product = Array();
                    $product["id"] = $item->getProductId();
                    $product["quantity"] = $item->getQty();
                    $product["type"] = $type;
                    $product["parent"] = $parent;

                    $product["price"] = $this->priceForTracking($item);
                    $product["displayPrice"] = $this->displayPriceForTracking($item);

                    $cart["products"][] = $product;
                }*/

            }
        }

        return $cart;
    }

    public function getRecommenders($page)
    {
        $recommenders = array();
        $extension_enabled = Mage::getStoreConfigFlag('brainsins_recommender_options/brainsins_recommender_general/enabled', Mage::app()->getStore()->getStoreId());
        $bskey = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_general/bs_key', Mage::app()->getStore()->getStoreId());
        $msg_not_available = Mage::helper('brainsins_recommender')->__('No available recommenders in this page');

        if (!$extension_enabled || $bskey == '')
            return array('value' => '', 'label' => $msg_not_available);

        $response = @file_get_contents(self::getApiUrl() . "recommender/retrieve.xml?token=" . $bskey);

        if ($response !== false) {
            $xmlData = simplexml_load_string($response);
            $jsonData = json_decode(json_encode((array)$xmlData), true);
            $recommenders[] = array('value' => '', 'label' => Mage::helper('brainsins_recommender')->__('--- Recommender name ---'));
            foreach ($jsonData['recommenders']['recommender'] as $recommender) {
                if ($recommender['page'] == $page)
                    $recommenders[] = array('value' => $recommender['id_recommender'], 'label' => $recommender['name']);
            }
            return $recommenders;
        } else
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
        if ($store == null)
            $store = Mage::app()->getStore()->getStoreId();
        $jSonRecommendersConfig = '';
        $config = array();
        if ($page == 'all') {
            $pages = explode(',', self::$_pages);
            foreach ($pages as $key => $_page) {
                $jSonRecommendersConfig = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_recommenders_' . $_page . '/recommenders_' . $_page, $store);
                $arrayDatas = unserialize($jSonRecommendersConfig);
                foreach ($arrayDatas as $key => $arrayData) {
                    foreach ($arrayData as $key => $data) {
                        $config[$key][] = $data;
                    }
                }
            }
        } else {
            $jSonRecommendersConfig = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_recommenders_' . $page . '/recommenders_' . $page, $store);
            $arrayDatas = unserialize($jSonRecommendersConfig);
            $config = array();
            if ($arrayDatas) {
                foreach ($arrayDatas as $key => $arrayData) {
                    foreach ($arrayData as $key => $data) {
                        $config[$key][] = $data;
                    }
                }
            }
        }

        return $this->_cleanEmpties($config);
    }

    public function getPageRecommenders($page)
    {
        $recommenders = $this->getConfiguration($page, Mage::app()->getStore()->getStoreId());
        $result = Array();

        if ($recommenders && count($recommenders) > 0) {
            foreach ($recommenders as $key => $_value) {
                if ($_value[0] == '' || !is_numeric($_value[0]))
                    continue;

                if ($_value[4] != '') {
                    $_location = $_value[4];
                    $_position = $_value[3];
                } else {
                    $_location = 'brainSINS_recommender_' . $_value[0];
                    $_position = 'replace';
                }
                $recommender = Array();
                $recommender["recommenderId"] = $_value[0];
                $recommender["location"] = $_location;
                $recommender["position"] = $_position;
                $recommender["detailsLevel"] = "high";

                $result [] = $recommender;
            }
        }

        return $result;
    }

    public function getRecommendersJs($recommenders)
    {
        $recommenders_js = '';
        if ($recommenders && count($recommenders) > 0) {
            $recommenders_js .= ',
					recommenders: [';
            foreach ($recommenders as $key => $_value) {
                if ($_value[0] == '' || !is_numeric($_value[0]))
                    continue;
                if ($_value[4] != '') {
                    $_location = $_value[4];
                    $_position = $_value[3];
                } else {
                    $_location = 'brainSINS_recommender_' . $_value[0];
                    $_position = 'replace';
                }
                $recommenders_js .= "
				{
						recommenderId: " . $_value[0] . ",
								detailsLevel : 'high',
								location: '" . $_location . "',
										position: '" . $_position . "'
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
            if ($value[0] == '')
                continue;
            else
                $cleanedData[] = $value;
        }

        return array_map('unserialize', array_unique(array_map('serialize', $cleanedData)));
    }

    public function getProductsFeed($key = '')
    {
        return "";
    }

    public function updateCartInBrainsins($cart, $special = false)
    {
        if (!is_object($cart))
            return;

        $cartId = $cart->getId();

        if (!$cartId) {
            return;
        }

        $cartData = $this->processQuote($cart);

        $ruta = "order/trackOrder.xml?";
        $url = self::getApiUrl() . $ruta . "token=" . Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_general/bs_key', Mage::app()->getStore()->getStoreId());
        //build XML
        $cartXML = new DOMDocument('1.0', 'UTF-8');
        $cartXML->xmlStandalone = true;
        //add roots
        $pRecsins = $cartXML->createElement('recsins');
        $pRecsins->setAttribute('version', '0.1');
        $cartXML->appendChild($pRecsins);

        $pOrders = $cartXML->createElement('orders');
        $pOrder = $cartXML->createElement('order');
        //Add order associate attrs
        $pIdBuyer = $cartXML->createElement('idBuyer', $this->_getUser());
        $pOrder->appendChild($pIdBuyer);

        $checkoutSession = Mage::getSingleton('checkout/session');
        $brainsins_qhash=$checkoutSession->getData("brainsins_qhash"); 

        $pCartMisc = $cartXML->createElement('cartMisc', json_encode(array("brainsins_qhash"=>$brainsins_qhash,"quoteId"=>$cartId)));   
        $pOrder->appendChild($pCartMisc);

        $pTotal = $cartXML->createElement('totalAmount', $cartData["totalAmount"]);
        $pOrder->appendChild($pTotal);

        $pOrders->appendChild($pOrder);
        $pRecsins->appendChild($pOrders);

        $pProducts = $cartXML->createElement('products');
        $pOrder->appendChild($pProducts);

        $cartItems = $cartData["products"];

        foreach ($cartItems as $cartItem) {
            $pProduct = $cartXML->createElement('product');

            $pProductType = $cartXML->createElement('productType', "product");
            $pProduct->appendChild($pProductType);

            $pIdProduct = $cartXML->createElement('idProduct', $cartItem["id"]);
            $pProduct->appendChild($pIdProduct);

            $pPrice = $cartXML->createElement('price', number_format($cartItem["price"], 2, '.', ''));
            $pProduct->appendChild($pPrice);

            $pDisplayPrice = $cartXML->createElement('displayPrice', $cartItem["displayPrice"]);
            $pProduct->appendChild($pDisplayPrice);

            $pParentId = null;
            if ($cartItem["parentId"]) {
                $pParentId = $cartXML->createElement('parentId', $cartItem["parentId"]);
            } else {
                $pParentId = $cartXML->createElement('parentId', '');
            }
            $pProduct->appendChild($pParentId);

            $pQuantity = $cartXML->createElement('quantity', $cartItem["quantity"]);
            $pProduct->appendChild($pQuantity);

            $pProducts->appendChild($pProduct);
            $cartId .= ":" . $cartItem["id"] . ":" . $cartItem["quantity"] . ":" . $cartItem["price"];
        }


        $lastTrackedCart = Mage::getSingleton('core/session')->getBrainsinsLastCartTracked();
        if ($cartId == $lastTrackedCart) {
            return;
        } else {
            Mage::getSingleton('core/session')->setBrainsinsLastCartTracked($cartId);
        }

        $content = $cartXML->saveXML($cartXML->documentElement);
        $result = $this->_sendBrainsinsWS($url, $content);
        //error_log(var_export($content,TRUE));
        return $result;
    }

    public function getTotalAmount(Mage_Sales_Model_Order $order) {
        $taxConfig = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_advanced/tracking_tax', Mage::app()->getStore()->getStoreId());
        $trackingAmountconfig = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_advanced/tracking_amount', Mage::app()->getStore()->getStoreId());

        $idsMultipleArray = Mage::getSingleton('core/session')->getOrderIds(true);
        $total = 0;

        $quote = $order->getQuote();
        if (!$quote) {
            $quoteId = $order->getQuoteId();
            $quote = Mage::getModel('sales/quote')->load($quoteId);
        }

        if ($trackingAmountconfig !== "1") {
            if ($idsMultipleArray && is_array($idsMultipleArray)) {
                foreach ($idsMultipleArray as $id) {
                    $order = Mage::getModel('sales/order')->loadByIncrementId($id);

                    if ($taxConfig == "1") {
                        //$total += $order->getBaseSubtotalInclTax();
                    } else {
                        //$total += $order->getBaseSubtotal();
                    }


                    if ($taxConfig == "1") {
                        $total += $this->toBaseCurrency($quote->getGrandTotal());
                    } else {

                        $totals = $quote->getTotals();
                        $tax = 0;

                        foreach ($totals as $total) {

                            $code = $total->getCode();
                            $value = $total->getValue();

                            if ($code == "tax") {
                                $tax = $value;
                            } else if ($code == "discount") {
                                $cart["discount"] = -$value;
                            }
                        }

                        $total += $this->toBaseCurrency($quote->getGrandTotal() - $tax);
                    }
                }
            } else {
                if ($taxConfig == "1") {
                    $total = $order->getBaseSubtotalInclTax();
                } else {
                    $total = $order->getBaseSubtotal();
                }

                if ($taxConfig == "1") {
                    $total = $this->toBaseCurrency($quote->getGrandTotal());
                } else {

                    $tax = 0;
                    $totals = $quote->getTotals();

                    foreach ($totals as $total) {

                        $code = $total->getCode();
                        $value = $total->getValue();

                        if ($code == "tax") {
                            $tax = $value;
                        } else if ($code == "discount") {
                            $cart["discount"] = -$value;
                        }
                    }

                    $total = $this->toBaseCurrency($quote->getGrandTotal() - $tax);
                }
            }
        }

       return $total;
    }

    public function onEndCheckout(Mage_Sales_Model_Order $order)
    {
        $taxConfig = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_advanced/tracking_tax', Mage::app()->getStore()->getStoreId());
        $trackingAmountconfig = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_advanced/tracking_amount', Mage::app()->getStore()->getStoreId());

        $idsMultipleArray = Mage::getSingleton('core/session')->getOrderIds(true);
        $firstElement = true;
        $total = $this->getTotalAmount($order);

        $ruta = "purchase/close?";
        $url = self::getApiUrl() . $ruta . "token=" . Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_general/bs_key', Mage::app()->getStore()->getStoreId());

        $email = $order->getCustomerEmail();

        $purchaseId = $order->getIncrementId();

        $url .= "&email=$email&idPurchase=$purchaseId";

        if ($total) {
            $url .= "&amount=$total";
        }

        if (array_key_exists("bsCoId", $_COOKIE)) {
            $coId = $_COOKIE['bsCoId'];
            if ($coId) {
                $url .= "&cookieId=$coId";
            }
        }
        $result = $this->_sendBrainsinsWS($url);

        return $result;
    }

    public function onPayment($order)
    {
        $idsMultipleArray = Mage::getSingleton('core/session')->getOrderIds(true);
        $firstElement = true;
        $total = $this->getTotalAmount($order);

        $ruta = "purchase/payment.json?";
        $url = self::getApiUrl() . $ruta . "token=" . Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_general/bs_key', Mage::app()->getStore()->getStoreId());

        $email = $order->getCustomerEmail();
        $purchaseId = $order->getIncrementId();
        $url .= "&email=$email&amount=$total&idPurchase=$purchaseId";
        $result = $this->_sendBrainsinsWS($url);
        return $result;
    }

    public function onOrderCancel($order) {

        $id = $order->getIncrementId();
        $ruta = "purchase/cancel.json?";
        $url = self::getApiUrl() . $ruta . "token=" . Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_general/bs_key', Mage::app()->getStore()->getStoreId());
        $url .= "&idPurchase=" . $id;
        $this->_sendBrainsinsWS($url, null, "application/json", true);
    }

    protected function _sendBrainsinsWS($url, $content = "", $contentType = "application/xml", $post = true)
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

            if ($post) {
                curl_setopt($ch, CURLOPT_POST, 1);
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: ' . $contentType));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 5000);

            $response = curl_exec($ch);
            curl_close($ch);
        } else {
            $opts = array('http' =>
                array(
                    'method' => 'POST',
                    'header' => 'Content-type: text/xml',
                    'content' => $content
                )
            );
            $context = stream_context_create($opts);
            $response = file_get_contents($url, false, $context);
        }
        
        return $response;
    }

    protected function _getUser()
    {
        if (isset($_COOKIE['bsUl']) && $_COOKIE['bsUl'] == 1)
            return $_COOKIE['bsUId'];
        elseif (isset($_COOKIE['bsUl']) && $_COOKIE['bsUl'] == 0)
            return $_COOKIE['bsCoId'];
        else
            return;
    }

    public function getStores($key)
    {
        $stores = Mage::app()->getStores();
        $store_ids = array();
        foreach ($stores as $k => $store) {
            $_storeId = $store->getStoreId();
            $bskey = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_general/bs_key', $_storeId);
            if ($key == $bskey) {
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
        foreach ($stores as $k => $store) {
            $_storeId = $store->getStoreId();
            $bskey = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_general/bs_key', $_storeId);
            if ($key == $bskey) {

                $currencies_ids[$_storeId] = Mage::app()->getStore($_storeId)->getCurrentCurrencyCode();
            }
        }
        return $currencies_ids;
    }

    public function getBundlePrice($_product, $return_type, $tax)
    {
        return Mage::getModel('bundle/product_price')->getTotalPrices($_product, $return_type, $tax);
    }

    public function getProductImage($product, $width = false, $height = false)
    {
        $imageUrl = null;
        $image_width = null;
        $image_height = null;

        if (!$width) {
            $image_width = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_feed/product_image_resize_width', Mage::app()->getStore()->getStoreId());
        } else {
            $image_width = $width;
        }

        if (!$height) {
            $image_height = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_feed/product_image_resize_height', Mage::app()->getStore()->getStoreId());
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
            $imageUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
        } else {
            $imageUrl = (string)Mage::helper('catalog/image')->init($product, "image")->resize($image_width, $image_height);
        }

        return $imageUrl;
    }

    public function getExtensionVersion()
    {
        return (string) Mage::getConfig()->getNode()->modules->Brainsins_Recommender->version;
    }


}
