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

class Brainsins_Recommender_TrackingController extends Mage_Core_Controller_Front_Action
{
	public function bdataAction()
	{
		try {
				
			/*if (!$bdata || $bdata == "") {
				$this->getResponse()->setHeader('Content-Type', 'application/json')->setBody("{}");
				return;
			}
			$bdata = json_decode($bdata, true);

			if (!$bdata) {
				$this->getResponse()->setHeader('Content-Type', 'application/json')->setBody("{}");
				return;
			}
			*/
			$bdata = $this->_completeBdata(Array());
			$response = json_encode($bdata);

		} catch (Exception $e) {
			$this->getResponse()->setHeader('Content-Type', 'application/json')->setBody($this->getRequest()->getParam('bdata'));
			return;
		}
		$this->getResponse()->setHeader('Content-Type', 'application/json')->setBody($response);
	}
	
	public function checkOrderAction() {
		
		$email = $this->getRequest()->getParam('email');
		$hours = $this->getRequest()->getParam('hours');
		
		if (!$email || $email == "") {
			$this->getResponse()->setHeader('Content-Type', 'application/json')->setBody('{"status":"error"}');
			return;
		}
		
		if (!$hours || $hours == "") {
			$hours = 72;
		}
		
		$customer = Mage::getModel("customer/customer");
		$customer->setWebsiteId(Mage::app()->getWebsite()->getId());
		$customer->loadByEmail($email);
		$id = $customer->getId();
		if ($id !== null) {
			
			$orders = Mage::getResourceModel('sales/order_collection')
			->addFieldToSelect('*')
			->addFieldToFilter('customer_id', $id)
			->addAttributeToSort('created_at', 'DESC')
			->setPageSize(1);
			
			$order = $orders->getFirstItem();
			
			if ($order->getId() !== null) {
				$now = new Zend_Date();
				$now->sub($hours, Zend_Date::HOUR);
				$date = $order->getCreatedAtStoreDate();
				
				if ($date->compare($now) == -1) {
					$this->getResponse()->setHeader('Content-Type', 'application/json')->setBody('{"status":"ok"}');
					return;
				} else {
					// date is in range
					$this->getResponse()->setHeader('Content-Type', 'application/json')->setBody('{"status":"wait"}');
					return;
				}
				
			} else {
				$this->getResponse()->setHeader('Content-Type', 'application/json')->setBody('{"status":"ok"}');
				return;
			}			
			
		} else {
			$this->getResponse()->setHeader('Content-Type', 'application/json')->setBody('{"status":"error"}');
			return;
		}
		
	}

	private function _completeBdata($bdata) {

		$bs_register = Mage::getSingleton('core/cookie')->get('brainsins_register');
		$bs_login = Mage::getSingleton('core/cookie')->get('brainsins_login');
		$bs_logout = Mage::getSingleton('core/cookie')->get('brainsins_logout');
		$bs_news = Mage::getSingleton('core/cookie')->get('brainsins_news');
		$email_tracking = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_general/email_tracking', Mage::app()->getStore()->getStoreId());

// 		$bdata["brainsins_register"] = $bs_register;
// 		$bdata["brainsins_login"] = $bs_login;
// 		$bdata["brainsins_logout"] = $bs_logout;
// 		$bdata["brainsins_news"] = $bs_news;
		
		Mage::getSingleton('core/cookie')->delete('brainsins_news');
		Mage::getSingleton('core/cookie')->set('brainsins_register', '0', time()+86400, '/', null, null, false);
		Mage::getSingleton('core/cookie')->set('brainsins_login', '0', time()+86400, '/', null, null, false);
		Mage::getSingleton('core/cookie')->set('brainsins_logout', '0', time()+86400, '/', null, null, false);
		Mage::getSingleton('core/cookie')->set('brainsins_news', '0', time()+86400, '/', null, null, false);
		
		
		if($bs_register && $bs_register != '0')
		{
			if($email_tracking == '1')
			{
				$customer_id = $bs_register;
				$customer = Mage::getModel('customer/customer')->load($customer_id);
				$subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($customer->getEmail());
				if($subscriber->getId() && $subscriber->getSubscriberStatus() == '1')
				{
					$lang = explode('_', Mage::app()->getLocale()->getLocaleCode());
					// 					echo 'BrainSINSData.userEmail = "' . $customer->getEmail() .'";
					// 							BrainSINSData.userNewsletter = 1;
					// 							BrainSINSData.login = 1;';
					$bdata['userEmail'] = $customer->getEmail();
					$bdata['userNewsletter'] = 1;
					$bdata['login'] = 1;
				}
				else
				{
					Mage::getSingleton('core/cookie')->delete('brainsins_register');
					$lang = explode('_', Mage::app()->getLocale()->getLocaleCode());
					// 					echo 'BrainSINSData.userEmail = "' . $customer->getEmail() .'";
					// 							BrainSINSData.userNewsletter = 0;
					// 							BrainSINSData.login = 1;';
					$bdata['userEmail'] = $customer->getEmail();
					$bdata['userNewsletter'] = 0;
					$bdata['login'] = 1;
				}

				unset($customer);unset($subscriber);
			}
			elseif($email_tracking == '2')
			{
				$customer_id = $bs_register;
				$customer = Mage::getModel('customer/customer')->load($customer_id);
				$lang = explode('_', Mage::app()->getLocale()->getLocaleCode());
				// 				echo 'BrainSINSData.userEmail = "' . $customer->getEmail() .'";
				// 						BrainSINSData.userNewsletter = 0;
				// 						BrainSINSData.login = 1;';
				$bdata['userEmail'] = $customer->getEmail();
				$bdata['userNewsletter'] = 0;
				$bdata['login'] = 1;
			}
			elseif($email_tracking == '0')
			{
				$customer_id = $bs_register;
				$customer = Mage::getModel('customer/customer')->load($customer_id);
				$lang = explode('_', Mage::app()->getLocale()->getLocaleCode());
				// 				echo 'BrainSINSData.userEmail = "' . $customer->getEmail() .'";
				// 						BrainSINSData.userNewsletter = 0;
				// 						BrainSINSData.login = 1;';
				$bdata['userEmail'] = $customer->getEmail();
				$bdata['userNewsletter'] = 0;
				$bdata['login'] = 1;
			}
			unset($customer);
		}
		if($bs_login && $bs_login != '0' && (!$bs_register || $bs_register == '0'))
		{
			if($email_tracking == '1')
			{
				$customer_id = $bs_login;
				$customer = Mage::getModel('customer/customer')->load($customer_id);
				$subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($customer->getEmail());
				if($subscriber->getId() && $subscriber->getSubscriberStatus() == '1')
				{
					Mage::getSingleton('core/cookie')->delete('brainsins_login');
					$lang = explode('_', Mage::app()->getLocale()->getLocaleCode());
					// 					echo 'BrainSINSData.userEmail = "' . $customer->getEmail() .'";
					// 							BrainSINSData.userNewsletter = 1;
					// 							BrainSINSData.login = 1;';
					$bdata['userEmail'] = $customer->getEmail();
					$bdata['userNewsletter'] = 1;
					$bdata['login'] = 1;
				}
				else
				{
					Mage::getSingleton('core/cookie')->delete('brainsins_login');
					$lang = explode('_', Mage::app()->getLocale()->getLocaleCode());
					// 					echo 'BrainSINSData.userEmail = "' . $customer->getEmail() .'";
					// 							BrainSINSData.userNewsletter = 0;
					// 							BrainSINSData.login = 1;';
					$bdata['userEmail'] = $customer->getEmail();
					$bdata['userNewsletter'] = 0;
					$bdata['login'] = 1;
				}
				unset($customer);unset($subscriber);
			}
			elseif($email_tracking == '2')
			{
				$customer_id = $bs_login;
				$customer = Mage::getModel('customer/customer')->load($customer_id);
				$lang = explode('_', Mage::app()->getLocale()->getLocaleCode());
				// 				echo 'BrainSINSData.userEmail = "' . $customer->getEmail() .'";
				// 						BrainSINSData.userNewsletter = 1;
				// 						BrainSINSData.login = 1;';
				$bdata['userEmail'] = $customer->getEmail();
				$bdata['userNewsletter'] = 1;
				$bdata['login'] = 1;
			}
			elseif($email_tracking == '0')
			{
				$customer_id = $bs_login;
				$customer = Mage::getModel('customer/customer')->load($customer_id);
				$lang = explode('_', Mage::app()->getLocale()->getLocaleCode());
				// 				echo 'BrainSINSData.userEmail = "' . $customer->getEmail() .'";
				// 						BrainSINSData.userNewsletter = 0;
				// 						BrainSINSData.login = 1;';
				$bdata['userEmail'] = $customer->getEmail();
				$bdata['userNewsletter'] = 1;
				$bdata['login'] = 1;
			}
			unset($customer);
		}

		if($bs_logout && $bs_logout != '0' && (!$bs_register || $bs_register == '0'))
		{
			// 			echo 'BrainSINSData.logout = 1;';
			$bdata['logout'] = 1;
		}

		if($bs_news && $bs_news != '0' && (!$bs_register || $bs_register == '0'))
		{
			$customer_id = Mage::getSingleton('core/cookie')->get('brainsins_news');
			$customer = Mage::getModel('customer/customer')->load($customer_id);
			$subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($customer->getEmail());
			$lang = explode('_', Mage::app()->getLocale()->getLocaleCode());
			if($subscriber->getSubscriberStatus() == '3')
			{
				// 				echo 'BrainSINSData.userEmail = "' . $customer->getEmail() .'";
				// 						BrainSINSData.userNewsletter = 0;
				// 						BrainSINSData.login = 1;';
				$bdata['userEmail'] = $customer->getEmail();
				$bdata['userNewsletter'] = 0;
				$bdata['login'] = 1;
			}
			elseif($subscriber->getSubscriberStatus() == '1')				{
				// 				echo 'BrainSINSData.userEmail = "' . $customer->getEmail() .'";
				// 						BrainSINSData.userNewsletter = 1;
				// 						BrainSINSData.login = 1;';
				$bdata['userEmail'] = $customer->getEmail();
				$bdata['userNewsletter'] = 1;
				$bdata['login'] = 1;
			}
			unset($customer);unset($subscriber);
		}

		return $bdata;
	}
}