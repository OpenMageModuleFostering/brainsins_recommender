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

class Brainsins_Recommender_Model_Cron
{	
	public function generateOfflineFeeds($bskey)
	{
		try
		{
			$this->_productsFeed($bskey);
			return true;
		}
		catch(Exception $e)
		{
			Mage::throwException(Mage::helper('brainsins_recommender')->__('ERROR GENERATING OFFLINE FEEDS'));
			return false;
		}
	}

	protected function _productsFeed($bskey)
	{
		$feed = Mage::helper('brainsins_recommender')->getProductsFeed($bskey);
		$io = new Varien_Io_File();
		$path = Mage::getBaseDir('media') . DS . 'brainsins_feeds' . DS;
		$file = $path . DS . $bskey . '.xml';
		$io->setAllowCreateFolders(true);
		$io->open(array('path' => $path));
		$io->streamOpen($file, 'w+');
		$io->streamLock(true);
		$io->streamWrite($feed);
		$io->streamClose();
	}
}