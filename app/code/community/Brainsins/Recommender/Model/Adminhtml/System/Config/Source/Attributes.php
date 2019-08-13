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

class Brainsins_Recommender_Model_Adminhtml_System_Config_Source_Attributes
{
	public function toOptionArray($isMultiSelect = false)
	{
		$_attributes = Mage::getResourceModel('catalog/product_attribute_collection')->getItems();
	    $options = array();
	    foreach($_attributes as $_code => $_attribute)
	    {
	    	if($_attribute->getFrontendLabel() == '')
				$_label = $_attribute->getAttributecode();
			else
				$_label = $_attribute->getFrontendLabel();
	        $options[] = array('value' => $_attribute->getAttributecode(), 'label' => $_label);
	    }
		array_unshift($options, array('value'=>'', 'label'=> Mage::helper('adminhtml')->__('--Please Select--')));	
	    return $options;
	}
}