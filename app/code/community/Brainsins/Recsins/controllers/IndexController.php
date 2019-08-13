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

class Brainsins_Recsins_IndexController extends Mage_Core_Controller_Front_Action {

    public function indexAction() {

        $model = Mage::getModel('recsins/recommender');
        $this->loadLayout();
        $this->renderLayout();
    }

    public function getRecProdImgAction() {

    	$id = $this->getRequest()->getParam("id");
    	$helper = Mage::helper("recsins/recsins");
    	$url = $helper->getImageUrl($id);
    	if (isset($url) && $url) {
    		$this->_redirectUrl($url);
    	}
    }
    
    public function getRecProdImgUrlAction() {

    	$id = $this->getRequest()->getParam("id");
    	$helper = Mage::helper("recsins/recsins");
    	$url = $helper->getImageUrl($id);

    	$baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
    	$url = "https://www.gemln.com/media/catalog/product/cache/7/small_image/149x155/9df78eab33525d08d6e5fb8d27136e95/T/r/Tresara-DiamondRing-GBRIN0060085-227.jpg";
    	$pos = strpos($url, "/", 8);
    	if ($pos !== false) {
    		$relativeUrl = substr($url, $pos);
    		return $relativeUrl;
    	} else {
    		return $url;
    	} 	
    }
    
    public function getRecProdImgRelativeUrl($id) {
    
    	$helper = Mage::helper("recsins/recsins");
    	$url = $helper->getImageUrl($id);
    
    	$baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
    	$pos = strpos($url, "/", 8);
    	if ($pos !== false) {
    		$relativeUrl = substr($url, $pos);
    		return $relativeUrl;
    	} else {
    		return $url;
    	}
    }
    
    /**
     * @return JSON Object
     */
    public function getRecommendationsAction() {
    	
    	
    	$recommenderId = $this->getRequest()->getParam("recId");
    	$userId = $this->getRequest()->getParam("userId");
    	$productId = $this->getRequest()->getParam("prodId");
    	$lang = $this->getRequest()->getParam("lang");
    	$divName = $this->getRequest()->getParam("divName");
    	$numRecs = $this->getRequest()->getParam("numRecs");
    	$categories = $this->getRequest()->getParam("categories");
    	$filter = $this->getRequest()->getParam("filter");
    	$detail = $this->getRequest()->getParam("detail");
    	$currSymbol = $this->getRequest()->getParam("currSymbol");
    	$currPosition = $this->getRequest()->getParam("currPosition");
    	$currDelimiter = $this->getRequest()->getParam("currDelimiter");
    	$callback = $this->getRequest()->getParam("callback");
    	
    	$model = Mage::getSingleton("recsins/recsins");
   		$model = new Brainsins_Recsins_Model_Recsins();
   		$result = $model->requestRecommendations($recommenderId, $productId, $userId, $lang, $divName, $categories, $filter);

   		$json = json_decode($result, true);		
   		
   		foreach($json["data"]["list"] as &$item) {
   			$item['imageUrl'] = $this->getRecProdImgRelativeUrl($item["id"]);
   		}
   		
   		$updatedJson = json_encode($json);	
   		
   		$script = "";
   		
   		if (!isset($callback) || $calback == "") {
   			$callback = "BrainSINSRecommender.paintRecommendations";
   		}
   		   		
   		$this->getResponse()->setHeader('Content-type', 'application/json');
   		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($json));
   		
   		return;
    }
}