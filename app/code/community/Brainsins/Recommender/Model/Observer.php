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

class Brainsins_Recommender_Model_Observer extends Mage_Core_Model_Abstract
{
	public function updateCartEvent($observer)
	{
		if($observer->getEvent()->getControllerAction()->getFullActionName() == 'checkout_cart_add' ||
			$observer->getEvent()->getControllerAction()->getFullActionName() == 'checkout_cart_delete' ||
			$observer->getEvent()->getControllerAction()->getFullActionName() == 'sales_order_reorder')
		{
			Mage::dispatchEvent('checkout_update_cart_after', array());
		}
		if($observer->getEvent()->getControllerAction()->getFullActionName() == 'checkout_cart_updatePost') //TO DO: Genera 2 llamadas en la actualización del carrito
			Mage::dispatchEvent('checkout_update_cart_after', array());
        
        if(Mage::app()->getRequest()->getRouteName().'/'.Mage::app()->getRequest()->getControllerName().'/'.Mage::app()->getRequest()->getActionName() == 'brainsins/cart/create') //Desde controller
            Mage::dispatchEvent('checkout_update_cart_after', array());
		
		return $this;
	}
	public function insertBlock($observer)
    {
        if (!Mage::getStoreConfigFlag('brainsins_recommender_options/brainsins_recommender_general/enabled', Mage::app()->getStore()->getStoreId()))
            return;

        $_recommenders = array();
        $_alias = $observer->getBlock()->getBlockAlias();

        //HOME PAGE RECOMMENDERS
        if (Mage::app()->getRequest()->getControllerName() . '/' . Mage::app()->getRequest()->getActionName() == 'index/index')
            $_recommenders = Mage::helper('brainsins_recommender')->getConfiguration('home', Mage::app()->getStore()->getStoreId());

        //PRODUCT PAGE RECOMMENDERS
        if (Mage::app()->getRequest()->getControllerName() . '/' . Mage::app()->getRequest()->getActionName() == 'product/view')
            $_recommenders = Mage::helper('brainsins_recommender')->getConfiguration('product', Mage::app()->getStore()->getStoreId());

        //CATEGORY PAGE RECOMMENDERS
        if (Mage::app()->getRequest()->getControllerName() . '/' . Mage::app()->getRequest()->getActionName() == 'category/view')
            $_recommenders = Mage::helper('brainsins_recommender')->getConfiguration('category', Mage::app()->getStore()->getStoreId());

        //CART PAGE RECOMMENDERS
        if (Mage::app()->getRequest()->getControllerName() . '/' . Mage::app()->getRequest()->getActionName() == 'cart/index')
            $_recommenders = Mage::helper('brainsins_recommender')->getConfiguration('cart', Mage::app()->getStore()->getStoreId());

        //THANK YOU PAGE RECOMMENDERS
        if (Mage::app()->getRequest()->getControllerName() . '/' . Mage::app()->getRequest()->getActionName() == 'onepage/success')
            $_recommenders = Mage::helper('brainsins_recommender')->getConfiguration('checkout', Mage::app()->getStore()->getStoreId());

        foreach ($_recommenders as $key => $_value) {
            if ($_alias == $_value[2] && $_value[2] != '') {
                $_before = '';
                $_after = '';
                $_recommender = $_value[0];
                $_position = $_value[1];
                $_block = $_value[2];
                $_custom_div_position = $_value[3];
                $_custom_div = $_value[4];
                $_content = $observer->getTransport()->getHtml();
                if ($_position == 'before') {
                    $_before = $this->_getBrainsinsRecommenderHtml($_recommender);
                    $observer->getTransport()->setHtml($_before . $_content);
                } elseif ($_position == 'after') {
                    $_after = $this->_getBrainsinsRecommenderHtml($_recommender);
                    $observer->getTransport()->setHtml($_content . $_after);
                }
            }
        }
    }
	
	public function updateCart($observer)
	{
		if(!Mage::getStoreConfigFlag('brainsins_recommender_options/brainsins_recommender_general/enabled', Mage::app()->getStore()->getStoreId()) || $this->_isApiRequest())
			return;
		Mage::helper('brainsins_recommender')->updateCartInBrainsins(Mage::getSingleton('checkout/session')->getQuote(), true);
	}

    public function customerRegister($observer)
    {
        if(!Mage::getStoreConfigFlag('brainsins_recommender_options/brainsins_recommender_general/enabled', Mage::app()->getStore()->getStoreId()) || $this->_isApiRequest())
            return;
        $customer = $observer->getCustomer();
        if (!$observer->getCustomer()->getOrigData()) {
            Mage::getSingleton('core/cookie')->set('brainsins_register', $customer->getId(), time()+86400, '/', null, null, false);
        }
    }

	public function customerLogin($observer)
	{
		if(!Mage::getStoreConfigFlag('brainsins_recommender_options/brainsins_recommender_general/enabled', Mage::app()->getStore()->getStoreId()) || $this->_isApiRequest())
			return;
		$customer = $observer->getCustomer();
		Mage::getSingleton('core/cookie')->set('brainsins_login', $customer->getId(), time()+86400, '/', null, null, false);
		
	}
	
	public function customerLogout($observer)
	{
		if(!Mage::getStoreConfigFlag('brainsins_recommender_options/brainsins_recommender_general/enabled', Mage::app()->getStore()->getStoreId()) || $this->_isApiRequest())
			return;
		$customer = $observer->getCustomer();
		Mage::getSingleton('core/cookie')->set('brainsins_logout', $customer->getId(), time()+86400, '/', null, null, false);
	}
	
	public function subscribedToNewsletter($observer)
	{
		if(!Mage::getStoreConfigFlag('brainsins_recommender_options/brainsins_recommender_general/enabled', Mage::app()->getStore()->getStoreId()) || $this->_isApiRequest())
			return;
		$event = $observer->getEvent();
	    $subscriber = $event->getDataObject();
		$statusChange = $subscriber->getIsStatusChanged();
	    if ($statusChange)
	    {
	    	Mage::getSingleton('core/cookie')->set('brainsins_news', $subscriber->getCustomerId(), time()+86400, '/', null, null, false);
	    }
	}
	
	public function onEndCheckout($observer) {
		try {		
		if(!Mage::getStoreConfigFlag('brainsins_recommender_options/brainsins_recommender_general/enabled', Mage::app()->getStore()->getStoreId()) || $this->_isApiRequest())
			return;
		Mage::helper('brainsins_recommender')->onEndCheckout($observer->getEvent()->getPayment()->getOrder());
		} catch (Exception $e) {
		}
	}
	
	public function onPayment($observer) {
		try {
			if(!Mage::getStoreConfigFlag('brainsins_recommender_options/brainsins_recommender_general/enabled', Mage::app()->getStore()->getStoreId()) || $this->_isApiRequest())
				return;
			Mage::helper('brainsins_recommender')->onPayment($observer->getEvent()->getInvoice()->getOrder());
		} catch (Exception $e) {
		}
	}

    public function onOrderCancel($observer) {
        try {
            if(!Mage::getStoreConfigFlag('brainsins_recommender_options/brainsins_recommender_general/enabled', Mage::app()->getStore()->getStoreId()) || $this->_isApiRequest())
                return;
            Mage::helper('brainsins_recommender')->onOrderCancel($observer->getEvent()->getOrder());
        } catch (Exception $e) {
        }
    }

	protected function _isApiRequest()
	{
		return Mage::app()->getRequest()->getModuleName() === 'api';
	}

	protected function _getBrainsinsRecommenderHtml($_recommender)
	{
		return '<div id="brainSINS_recommender_'.$_recommender.'"></div>';
	}
}
