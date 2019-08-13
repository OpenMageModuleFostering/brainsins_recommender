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
 
class Brainsins_Recommender_FeedsController extends Mage_Core_Controller_Front_Action
{
    public function productsAction()
    {
    	$key_param = $this->getRequest()->getParam('key');
    	if(!$key_param || !isset($key_param))
    	{
    		echo '[INVALID PARAMETERS]';die;
    	}
        $feed = Mage::helper('brainsins_recommender')->getProductsFeed($key_param);
        $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/xml')->setBody($feed);
    }
}
