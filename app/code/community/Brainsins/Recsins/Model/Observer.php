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

class Brainsins_Recsins_Model_Observer {

    private function getUserId() {

        $session = Mage::getSingleton('customer/session');
        if (!isset($session)) {
            return '0';
        }

        $customer = $session->getCustomer();
        if (!isset($customer)) {
            return '0';
        }

        $userId = $customer->getId();

        if (!isset($userId) || !$userId) {
            if (array_key_exists('coId', $_COOKIE)) {
                $userId = $_COOKIE['coId'];
                if (!is_numeric($userId)) {
                    $userId = '0';
                }
            } else {
                $userId = '0';
            }
        }
        return $userId ? $userId : '0';
    }

    public function onLogin($observer) {
        $enabled = Mage::getStoreConfig('brainsins/BS_ENABLED');
        if ($enabled !== '1') {
            return $this;
        }
        $session = Mage::getSingleton("core/session", array("name" => "frontend"));
        $session->setData("recsins_login", "1");
        return $this;
    }

    public function onCart($observer) {
        $enabled = Mage::getStoreConfig('brainsins/BS_ENABLED');
        if ($enabled !== '1') {
            return $this;
        }
        $session = Mage::getSingleton("core/session", array("name" => "frontend"));
        $session->setData("recsins_cart", "1");
        return $this;
    }

    public function onCheckoutSuccess($observer) {
        $enabled = Mage::getStoreConfig('brainsins/BS_ENABLED');
        if (!isset($enabled) || $enabled !== '1') {
            return $this;
        }

        $userId = $this->getUserId();
        $recsinsModel = Mage::getModel("recsins/recsins");
        $recsinsModel->trackCheckoutBegin($userId);
        $recsinsModel->trackCheckoutSuccess($observer->getEvent()->getPayment()->getOrder(), $userId);
        return $this;
    }

    public function onCheckoutStart($observer) {
        $enabled = Mage::getStoreConfig('brainsins/BS_ENABLED');
        if (!isset($enabled) || $enabled !== '1') {
            return $this;
        }

        $userId = $this->getUserId();
        $recsinsModel = Mage::getModel("recsins/recsins");
        $recsinsModel->trackCheckoutBegin($userId);
        return $this;
    }

    public function onCreateAccount($observer) {

    }
    
    public function onCustomerSave($observer) {
    	$subscriber = $observer->getEvent()->getSubscriber();
    	$userId = $subscriber->getCustomerId();
    	$email = "";
    	if ($subscriber->isSubscribed()) {
    		$email = $subscriber->getEmail();
    	}
    	$recsinsModel = Mage::getModel("recsins/recsins");
    	$res = $recsinsModel->sendUpdatedUser($userId, $email);

    }
}
