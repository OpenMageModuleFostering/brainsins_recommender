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
 
class Brainsins_Recommender_CartController extends Mage_Core_Controller_Front_Action
{
    public function createAction()
    {
    	if(!Mage::getStoreConfigFlag('brainsins_recommender_options/brainsins_recommender_general/enabled', Mage::app()->getStore()->getStoreId()))
		{
			$this->_redirectUrl(Mage::getBaseUrl());  
			$this->setFlag('', self::FLAG_NO_DISPATCH, true);  
			return $this;
		}
		
		if($this->getRequest()->getParam('brainsins_qhash'))
		{
            $brainsins_qhash=$this->getRequest()->getParam('brainsins_qhash');
            $session = Mage::getSingleton('checkout/session');
            try{   
                //get carts info
                $oldQuote=Mage::getModel('sales/quote')->load($brainsins_qhash,'brainsins_qhash');
                $currentQuote=Mage::getModel('sales/quote')->load($session->getQuoteId());
                if($currentQuote->getId()===null){
                    //replace it with the old one
                    $session->replaceQuote($oldQuote);
                    //add quote info to BrainSINSData tracking, since a new quote has been created
                    $newQuote=$session->getQuote();
                    $session->setData("quoteId",$newQuote->getId());
                    $session->setData("brainsins_qhash",$newQuote->brainsins_qhash);
                }else{        
                    //merge old cart with current cart
                    $currentQuote->merge($oldQuote);
                    $currentQuote->collectTotals()->save();
                } 
            }catch(Exception $e){
                error_log("[BrainSINS]".$e);
            }
		}
        $this->_redirect('checkout/cart/', array('_query' => $this->getRequest()->getParams()));
    }
    
    public function addAction($product, $qty) {
		$params = $this->getRequest()->getParams();
		$params['form_key'] = Mage::getSingleton('core/session')->getFormKey(); 
    	$this->_redirect('checkout/cart/add', $params);
    }

    protected function _checkIfProductExistsInCart($id_product, $products)
    {
        foreach ($products as $product)
        {
            if($product->getProductId() == $id_product)
                return true;
        }
        return false;
    }
    
    protected function _checkIfProductExists($id_product)
    {
        $product = new Mage_Catalog_Model_Product();
        $product->load($id_product); 
        if($product->getId())
            return true;
        return false;
    }
    
    protected function _getDefaultProduct($product, $type)
    {
        if($type == 'configurable')
        {
            $childs = $product->getTypeInstance()->getUsedProducts();
            foreach ($childs as $key => $child)
            {
                if($child->isSaleable())
                    return $child->getId();
            }
        }
        if($type == 'grouped')
        {
            if($product->isGrouped())
            {
                $groupedProducts = Mage::getModel('catalog/product_type_grouped')->setProduct($product)->getAssociatedProducts();
                foreach($groupedProducts as $p)
                {
                    if($p->isSaleable())
                        return $p->getId();
                }
            }
        }
        if($type == 'bundle')
        {
            //TODO Qué opciones e ítems coger?
            return false;
            /*
            $optionCollection = $product->getTypeInstance(true)->getOptionsCollection($product);
            foreach ($optionCollection as $prdOptions)
            {

            }
             */
        }
        return false;
    }
    
    
}
