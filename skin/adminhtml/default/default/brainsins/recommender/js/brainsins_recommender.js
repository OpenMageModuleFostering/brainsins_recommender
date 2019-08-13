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

Event.observe(window, 'load', function() {
	if($('brainsins_recommender_options_brainsins_recommender_general_bs_key').value == '')
	{
		$('brainsins_recommender_options_brainsins_recommender_general').down(2).show();
	}
	if($('urlOfflineFeedController'))
	{
		runOfflineFeed($('urlOfflineFeedController').value, $('urlOfflineFeedFile').value, '');
		$('btnRunOfflineFeed').hide();
	}
	else
	{
		$('btnRunOfflineFeed').removeClassName('disabled');
		$('btnRunOfflineFeed').removeClassName('disabled');
		$('btnRunOfflineFeed').enable();
		$('btnViewOfflineFeed').removeClassName('disabled');
		$('btnViewOfflineFeed').removeClassName('disabled');
		$('btnViewOfflineFeed').enable();
		$('btnViewOnlineFeed').removeClassName('disabled');
		$('btnViewOnlineFeed').removeClassName('disabled');
		$('btnViewOnlineFeed').enable();
	}
	if($('brainsins_recommender_options_product_feed_product_description_attribute').value == '')
	{
		var options = $('brainsins_recommender_options_product_feed_product_description_attribute');
		var len = options.length;
		for (var i = 0; i < len; i++) {
			if(options[i].value == 'description')
			{
				options[i].selected = true;
			}
		}
		$('brainsins_recommender_options_product_feed_product_description_attribute').value == 'description';
	}
});

function runOfflineFeed(url, file, bskey) {
	var now = new Date();
	//var msg = file + ' (' + FormatNu(now.getDay()-1) + '/' + FormatNu(now.getMonth()+1) + '/' + now.getFullYear() + ' ' + now.getHours() + ':' + FormatNu(now.getMinutes()) + ')';
	var msg = file + ' (' + now.getHours() + ':' + FormatNu(now.getMinutes()) + ':' + FormatNu(now.getSeconds()) + ')';
	if(bskey != '')
	{
		url = url + 'bskey/' + bskey;
	}
	new Ajax.Request(url, {
		onSuccess: function(response) {
			$$('.url-feed-offline')[0].update(msg);
		},
		onComplete: function(response) {
		if (200 == response.status)
			if($$('.feed-filenok')[0])
		  	{
				$$('.feed-filenok')[0].update(msg);
				$$('.feed-filenok')[0].toggleClassName('url-feed-offline');
				$$('.feed-filenok')[0].removeClassName('feed-filenok');
		  	}
  		}
	});
}

Validation.addAllThese([
    ['validate-bskey-format', 'BrainSINS Key not valid.', function (v) {
		return Validation.get('IsEmpty').test(v) || /^BS-\d{10}-\d+$/.test(v)
	}],
    ['required-recommender-home', 'Recommender name is required.', function (v,elm) {
		if(elm.up(2).getStyle('display') == 'block')
		{
	    	result = ((v != "none") && (v != null) && (v.length != 0));
	    	if(!result)
	    	{
	    		goToError('brainsins_recommender_recommenders_container_home');
	    		return false;
	    	}
	    	else
	    	{
	    		return true;
	    	}
	    }
	    else
	    {
	    	return true;
	    }
	}],
    ['required-recommender-product', 'Recommender name is required.', function (v,elm) {
		if(elm.up(2).getStyle('display') == 'block')
		{
	    	result = ((v != "none") && (v != null) && (v.length != 0));
	    	if(!result)
	    	{
	    		goToError('brainsins_recommender_recommenders_container_product');
	    		return false;
	    	}
	    	else
	    	{
	    		return true;
	    	}
	    }
	    else
	    {
	    	return true;
	    }
	}],
    ['required-recommender-category', 'Recommender name is required.', function (v,elm) {
		if(elm.up(2).getStyle('display') == 'block')
		{
	    	result = ((v != "none") && (v != null) && (v.length != 0));
	    	if(!result)
	    	{
	    		goToError('brainsins_recommender_recommenders_container_category');
	    		return false;
	    	}
	    	else
	    	{
	    		return true;
	    	}
	    }
	    else
	    {
	    	return true;
	    }
	}],
    ['required-recommender-cart', 'Recommender name is required.', function (v,elm) {
		if(elm.up(2).getStyle('display') == 'block')
		{
	    	result = ((v != "none") && (v != null) && (v.length != 0));
	    	if(!result)
	    	{
	    		goToError('brainsins_recommender_recommenders_container_cart');
	    		return false;
	    	}
	    	else
	    	{
	    		return true;
	    	}
	    }
	    else
	    {
	    	return true;
	    }
	}],
    ['required-recommender-checkout', 'Recommender name is required.', function (v,elm) {
		if(elm.up(2).getStyle('display') == 'block')
		{
	    	result = ((v != "none") && (v != null) && (v.length != 0));
	    	if(!result)
	    	{
	    		goToError('brainsins_recommender_recommenders_container_checkout');
	    		return false;
	    	}
	    	else
	    	{
	    		return true;
	    	}
	    }
	    else
	    {
	    	return true;
	    }
	}],
    ['required-position-or-custom-div-home', 'Position or custom div is required.', function (v,elm) {
		if(elm.up(2).getStyle('display') == 'block')
		{
			custom_div = elm.next().down(1).next();
			if((v != "none") && (v != null) && (v != '-') && (v != 'custom') && (v.length != 0))
			{
				return true;
			}
			else
			{
				if((custom_div.value != '') && (custom_div.length != 0))
				{
					return true;
				}
				else
				{
					goToError('brainsins_recommender_recommenders_container_home');
					return false;
				}
			}
	    }
	    else
	    {
	    	return true;
	    }
	}],
	['required-position-or-custom-div-product', 'Position or custom div is required.', function (v,elm) {
		if(elm.up(2).getStyle('display') == 'block')
		{
			custom_div = elm.next().down(1).next();
			if((v != "none") && (v != null) && (v != '-') && (v != 'custom') && (v.length != 0))
			{
				return true;
			}
			else
			{
				if((custom_div.value != '') && (custom_div.length != 0))
				{
					return true;
				}
				else
				{
					goToError('brainsins_recommender_recommenders_container_product');
					return false;
				}
			}
	    }
	    else
	    {
	    	return true;
	    }
	}],
	['required-position-or-custom-div-category', 'Position or custom div is required.', function (v,elm) {
		if(elm.up(2).getStyle('display') == 'block')
		{
			custom_div = elm.next().down(1).next();
			if((v != "none") && (v != null) && (v != '-') && (v != 'custom') && (v.length != 0))
			{
				return true;
			}
			else
			{
				if((custom_div.value != '') && (custom_div.length != 0))
				{
					return true;
				}
				else
				{
					goToError('brainsins_recommender_recommenders_container_category');
					return false;
				}
			}
	    }
	    else
	    {
	    	return true;
	    }
	}],
    ['required-position-or-custom-div-cart', 'Position or custom div is required.', function (v,elm) {
		if(elm.up(2).getStyle('display') == 'block')
		{
			custom_div = elm.next().down(1).next();
			if((v != "none") && (v != null) && (v != '-') && (v != 'custom') && (v.length != 0))
			{
				return true;
			}
			else
			{
				if((custom_div.value != '') && (custom_div.length != 0))
				{
					return true;
				}
				else
				{
					goToError('brainsins_recommender_recommenders_container_cart');
					return false;
				}
			}
	    }
	    else
	    {
	    	return true;
	    }
	}],
    ['required-position-or-custom-div-checkout', 'Position or custom div is required.', function (v,elm) {
    	if(elm != $$('.required-position-or-custom-div-checkout')[0])
		{
			if(elm.up(2).getStyle('display') == 'block')
			{
				custom_div = elm.next().down(1).next();
				if((v != "none") && (v != null) && (v != '-') && (v != 'custom') && (v.length != 0))
				{
					return true;
				}
				else
				{
					if((custom_div.value != '') && (custom_div.length != 0))
					{
						return true;
					}
					else
					{
						goToError('brainsins_recommender_recommenders_container_checkout');
						return false;
					}
				}
		    }
		    else
		    {
		    	return true;
		    }	
		}
		return true;
	}]
]);

function checkCustom(object) {
	if(object.value == 'custom')
	{
		object.next().down().show();
		object.next().down().next().toggleClassName('custom-select-after-before-div');
		object.next().down().next().removeClassName('custom-select-after-before-div-hide');
		object.previous().toggleClassName('custom-select-after-before-hide');
		object.previous().removeClassName('custom-select-after-before');
		object.next().down().next().next().show();
	}
	else
	{
		object.next().down().hide();
		object.next().down().next().removeClassName('custom-select-after-before-div');
		object.next().down().next().addClassName('custom-select-after-before-div-hide');
		object.next().down().next().next().setValue('');
		object.next().down().next().next().hide();
		object.previous().addClassName('custom-select-after-before');
		object.previous().removeClassName('custom-select-after-before-hide');
	}
}

function goToError(div) {
    Effect.ScrollTo(div, {duration:'1.4', offset: -50});
}

function FormatNu(nu) {
	return nu>9?nu:'0'+nu;
}
