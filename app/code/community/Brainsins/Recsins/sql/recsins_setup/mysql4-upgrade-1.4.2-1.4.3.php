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
$installer = $this;

$table = $installer->getTable('recsins_recommender');

$installer->startSetup();

if (!$installer->tableExists($table)) {
	$installer->run("
	CREATE TABLE IF NOT EXISTS {$table} (
	  `id` int(10) unsigned NOT NULL,
	  `name` varchar(255),
	  `page` smallint(8),
	  PRIMARY KEY  (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Brainsins loaded Recommenders';
	");
}

Mage::getModel('core/config')->saveConfig('brainsins/BS_VERSION', '1.4.3');

$installer->endSetup();

Mage::getConfig()->cleanCache();