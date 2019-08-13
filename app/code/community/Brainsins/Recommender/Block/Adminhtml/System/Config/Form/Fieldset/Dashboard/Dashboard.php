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

class Brainsins_Recommender_Block_Adminhtml_System_Config_Form_Fieldset_Dashboard_Dashboard extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
    	$dashboard_url = 'http://analytics.brainsins.com/';
		$html = '
			<div id="dashboard" style="margin-top: -18px">
				<iframe	style="border-bottom:3px solid #dfdfdf" id="dashboardiframe" frameborder=0 src="'.$dashboard_url.'" height=700 width=100% scrolling="yes"></iframe>
			</div>
			<a href="http://analytics.brainsins.com" target="_newWindow">'.Mage::helper('brainsins_recommender')->__('Access the BrainSINS dashboard in a new window').'</a>.
			<script langauge="javascript">
				function GetHeight() {
				        var y = 0;
				        if (self.innerHeight) {
				                y = self.innerHeight;
				        } else if (document.documentElement && document.documentElement.clientHeight) {
				                y = document.documentElement.clientHeight;
				        } else if (document.body) {
				                y = document.body.clientHeight;
				        }
				        return y;
				}
				
				function doResize() {
				    document.getElementById("dashboardiframe").style.height= (GetHeight() - 180) + "px";
				}
				
				window.onresize = doResize;
				doResize();
			</script>
			';
        return $html;
    }
}
