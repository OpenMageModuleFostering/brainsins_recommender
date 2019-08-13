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
 
class Brainsins_Recommender_Model_Adminhtml_System_Config_Backend_Cron extends Mage_Core_Model_Config_Data
{
    const CRON_STRING_PATH = 'crontab/jobs/brainsins_recommender_feeds_cron/schedule/cron_expr';
    const CRON_MODEL_PATH = 'crontab/jobs/brainsins_recommender_feeds_cron/run/model';

    protected function _afterSave()
    {
        $extension_enabled = $this->getData('groups/brainsins_recommender_general/fields/enabled/value');
		$cron_enabled = $this->getData('groups/brainsins_recommender_feed/fields/cron_enabled/value');

        $time = $this->getData('groups/brainsins_recommender_feed/fields/cron_time/value');
        $frequency = $this->getData('groups/brainsins_recommender_feed/fields/cron_frequency/value');
        $frequencyDaily = Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_DAILY;
        $frequencyWeekly = Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_WEEKLY;
        $frequencyMonthly = Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_MONTHLY;
        $cronDayOfWeek = date('N');
        $cronExprArray = array(
            intval($time[1]),                                   # Minute
            intval($time[0]),                                   # Hour
            ($frequency == $frequencyMonthly) ? '1' : '*',      # Day of the Month
            '*',                                                # Month of the Year
            ($frequency == $frequencyWeekly) ? '1' : '*',       # Day of the Week
        );
		
		$value = $this->getValue();
        $cronExprString = join(' ', $cronExprArray);
        
        try
        {
            Mage::getModel('core/config_data')
                ->load(self::CRON_STRING_PATH, 'path')
                ->setValue($cronExprString)
                ->setPath(self::CRON_STRING_PATH)
                ->save();
			Mage::getModel('core/config_data')
			    ->load(self::CRON_MODEL_PATH, 'path')
			    ->setValue((string) Mage::getConfig()->getNode(self::CRON_MODEL_PATH))
			    ->setPath(self::CRON_MODEL_PATH)
			    ->save();
				
				if($cron_enabled = '0' || $extension_enabled == '0')
				{
		            Mage::getModel('core/config_data')
		                ->load(self::CRON_STRING_PATH, 'path')
		                ->setValue('')
		                ->setPath(self::CRON_STRING_PATH)
		                ->save();
					Mage::getModel('core/config_data')
					    ->load(self::CRON_MODEL_PATH, 'path')
					    ->setValue('')
					    ->setPath(self::CRON_MODEL_PATH)
					    ->save();
				}
        }
        catch (Exception $e)
        {
			throw new Exception(Mage::helper('cron')->__('Unable to save Cron expression'));
        }
    }
}