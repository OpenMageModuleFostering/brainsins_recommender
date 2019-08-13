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

class Brainsins_Recommender_LoginController extends Mage_Core_Controller_Front_Action
{
	public function loginAction() {

		$bsEnabled = Mage::getStoreConfigFlag('brainsins_recommender_options/brainsins_recommender_general/enabled', Mage::app()->getStore()->getStoreId());
		$autologinEnabled = Mage::getStoreConfigFlag('brainsins_recommender_options/brainsins_recommender_autologin/enabled', Mage::app()->getStore()->getStoreId());
		$autologinSecret = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_advanced/secret', Mage::app()->getStore()->getStoreId());

		$email = $this->getRequest()->getParam('email');
		$token = $this->getRequest()->getParam('token');

		$redirect = $this->getRequest()->getParam('redirect');
		if (!$redirect) {
			$redirect = "";
		}

		if (!$bsEnabled || !$autologinEnabled || !$autologinSecret || $autologinSecret == "") {
			$this->_redirect($redirect);
			return;
		}
		
		if(Mage::getSingleton('customer/session')->isLoggedIn()){
			$loggedCustomer = Mage::helper('customer')->getCustomer();
			$loggedEmail = $loggedCustomer->getEmail();
			
			if($loggedEmail == $email) {
				// this customer is already logged in
				$this->_redirect($redirect);
				return;
			}
		}
		
		$validation = md5($email . $autologinSecret) === $token;

		if (!$validation) {
			$this->_redirect($redirect);
			return;
		}

		$customer = Mage::getModel("customer/customer");
		$customer->setWebsiteId(Mage::app()->getWebsite()->getId());
		$customer->loadByEmail($email);

		if($customer->getId() ) {
			if(Mage::getSingleton('customer/session')->isLoggedIn()){
				Mage::getSingleton('customer/session')->logOut();
				$this->_redirect('*/*/login', array('_query' => $this->getRequest()->getParams()));
				return;
			}
			Mage::getSingleton('core/cookie')->set('brainsins_logout', '0', time()+86400, '/', null, null, false);
			Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
			$quote = Mage::getModel('sales/quote')->loadByCustomer($customer);
			if (count($quote->getAllItems()) == 0) {
				$redirect = "";
			}
		}

		$this->_redirect($redirect);
	}

}
