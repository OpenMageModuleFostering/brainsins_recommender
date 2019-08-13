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

class Brainsins_Recommender_Model_Adminhtml_System_Config_Source_Currencies
{
	public function toOptionArray()
	{
        $currencies = Array();
        $result = Array();

        $default = Array();
        $default["value"] = "";
        $default["label"] = "use store's base currency";
        $result[] = $default;

        $stores = Mage::app()->getStore()->getCollection();

        foreach ($stores as $store) {

            $currencies[] = Array();
            $codes = $store->getAvailableCurrencyCodes(true);
            foreach ($codes as $code) {
                if (!in_array($code, $currencies)) {
                    $currencies[] = $code;
                    $codeResult = Array();
                    $codeResult['value'] = $code;
                    $codeResult['label'] = $code;
                    $result[] = $codeResult;
                }
            }
        }



		return $result;
	}
}