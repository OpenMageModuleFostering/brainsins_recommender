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

class Brainsins_Recommender_Block_Adminhtml_System_Config_Source_Feeds_ProductOffline extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	protected $_viewButtonHtml = array();
	protected $_runButtonHtml = array();
	protected $_urlOfflineFeedController = array();

	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
	{
		$this->setElement($element);
 		$html = '<div id="brainsins_recommender_feeds_template">';
		$html .= $this->_getRowTemplateHtml();
		$html .= '</div>';
		return $html;
	}

	protected function _getRowTemplateHtml($rowIndex = 0)
	{
	    if(Mage::app()->getRequest()->getParam('store') != '')
            $bskey = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_general/bs_key', Mage::app()->getStore(Mage::app()->getRequest()->getParam('store'))->getStoreId());
        elseif(Mage::app()->getRequest()->getParam('store') == '' && Mage::app()->getRequest()->getParam('website') != '')
        {
            Mage::app()->setCurrentStore(Mage::app()->getWebsite(Mage::app()->getRequest()->getParam('website'))->getDefaultGroup()->getDefaultStoreId());
            $code = Mage::getSingleton('adminhtml/config_data')->getStore();
            $store_id = Mage::getModel('core/store')->load($code)->getId();
            $bskey = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_general/bs_key', Mage::app()->getWebsite(Mage::app()->getRequest()->getParam('website'))->getDefaultGroup()->getDefaultStoreId());
            Mage::app()->setCurrentStore(0);
        }
        else
            $bskey = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_general/bs_key', Mage::app()->getStore()->getStoreId());

		$_feedfile = Mage::getBaseDir('media').DS.'brainsins_feeds/'.$bskey.'.xml';
		$_file_exists = file_exists($_feedfile);
		$_url = str_replace('/index.php', '', Mage::getBaseUrl()).'media/brainsins_feeds/'.$bskey.'.xml';
		if($_file_exists)
		{
			$html = '<div style="margin:5px 0 10px;">';
			$html .= $this->_getProductsFeedUrlHtml('', $_url, $_feedfile);
			$html .= $this->_getViewButtonHtml('li', Mage::helper('brainsins_recommender')->__('View'), $_url);
			$html .= $this->_getRunButtonHtml('li', Mage::helper('brainsins_recommender')->__('Run'), $_url, $bskey);
			$html .= '</div>';
		}
		else
		{
			$html = '<div style="margin:5px 0 10px;">';
			$html .= '<span class="feed-filenok">'.Mage::helper('brainsins_recommender')->__('The offline feed is not generated yet.').'</span>';
			$html .= $this->_getRunButtonHtml('li', Mage::helper('brainsins_recommender')->__('Generate'), $_url, $bskey);
			$html .= '</div>';
			$html .= $this->_getUrlOfflineFeedControllerHtml('li', '', $_url, $bskey);
		}

		return $html;
	}

	protected function _getDisabled()
	{
		return $this->getElement()->getDisabled() ? ' disabled' : '';
	}

	protected function _getValue($key)
	{
		return $this->getElement()->getData('value/' . $key);
	}

	protected function _getSelected($key, $value)
	{
		return $this->getElement()->getData('value/' . $key) == $value ? 'selected="selected"' : '';
	}
	
	protected function _getRadioSelected($key, $value)
	{
		return $this->getElement()->getData('value/' . $key) == $value ? 'checked="yes"' : '';
	}

	protected function _getViewButtonHtml($selector = 'li', $title = 'View', $url = '')
	{
		if (!$this->_viewButtonHtml)
		{
			$this->_viewButtonHtml = $this->getLayout()->createBlock('adminhtml/widget_button')
				->setType('button')
                ->setId('btnViewOfflineFeed')
				->setClass('scalable go custom' . $this->_getDisabled())
				->setLabel($this->__($title))
				->setOnClick("window.open('" . $url . "')")
				->setDisabled($this->_getDisabled())
				->toHtml();
		}
		return $this->_viewButtonHtml;
	}
	
	protected function _getRunButtonHtml($selector = 'li', $title = 'Run', $url = '', $bskey = '')
	{
		if (!$this->_runButtonHtml)
		{
			$this->_runButtonHtml = $this->getLayout()->createBlock('adminhtml/widget_button')
				->setType('button')
				->setId('btnRunOfflineFeed')
				->setClass('scalable go custom' . $this->_getDisabled())
				->setLabel($this->__($title))
				->setOnClick("runOfflineFeed('".Mage::helper('adminhtml')->getUrl('brainsins_recommender/adminhtml_feeds/run')."','".$url."','".$bskey."')")
				->setDisabled($this->_getDisabled())
				->toHtml();
		}
		return $this->_runButtonHtml;
	}
	
	protected function _getUrlOfflineFeedControllerHtml($selector = 'li', $title = 'Run', $url = '', $bskey)
	{
		$_url = str_replace('/index.php', '', Mage::getBaseUrl()).'media/brainsins_feeds/'.$bskey.'.xml';
		return '<input type="hidden" id="urlOfflineFeedController" value="'.Mage::helper('adminhtml')->getUrl('brainsins_recommender/adminhtml_feeds/run').'bskey/'.$bskey.'" />
				<input type="hidden" id="urlOfflineFeedFile" value="'.$_url.'" />';
	}

	protected function _getProductsFeedUrlHtml($default_value = '', $_url = '', $_feedfile)
	{
		return '<span class="url-feed-offline">'.$_url.' ('.date ("d/m/Y H:i", filemtime($_feedfile)).')</span>';
	}
}