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
 
class Brainsins_Recommender_PurchaseController extends Mage_Core_Controller_Front_Action
{

    private function _check($pids, $token) {
        $autologinSecret = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_advanced/secret', Mage::app()->getStore()->getStoreId());
        $validation = md5($pids . $autologinSecret) === strtolower($token);
        return $validation;
    }

    public function checkAction()
    {
        $result = Array();

        if (!Mage::getStoreConfigFlag('brainsins_recommender_options/brainsins_recommender_general/enabled', Mage::app()->getStore()->getStoreId())) {
            $this->_redirectUrl(Mage::getBaseUrl());
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            $output = json_encode($result);
            $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody($output);
            return;
        }

        $limit = 100;

        $csvPids = $this->getRequest()->getParam('pids');
        if ($csvPids && $csvPids != "") {
            $pids = preg_split("/,/", $csvPids);
            if ($pids && sizeof($pids) > 0) {
                $pids = array_slice($pids, 0, $limit);
                $token = $this->getRequest()->getParam('token');
                if (!$this->_check($csvPids, $token)) {
                    $output = json_encode($result);
                    $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody($output);
                    return;
                }
                $orderCollection = Mage::getSingleton("sales/order")->getCollection();
                $orderCollection->addAttributeToSelect("increment_id");
                $orderCollection->addAttributeToSelect("status");
                $orderCollection->addAttributeToFilter("increment_id", array("in" => $pids));
                foreach($orderCollection as $order) {
                    $result[$order->getIncrementId()] = $order->getStatus();
                }
            }
        }

        $output = json_encode($result);
        $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json')->setBody($output);
    }
}
