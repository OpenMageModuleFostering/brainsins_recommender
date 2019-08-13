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
*  BrainSINS' Magento Extension is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  You should have received a copy of the GNU General Public License
*  along with BrainSINS' Magento Extension.  If not, see
*  <http://www.gnu.org/licenses/>.
*
*  Please do not hesitate to contact us at info@brainsins.com
*
*/

final class BS_Radios extends Varien_Data_Form_Element_Radios {

	public function __construct($attributes=array()) {
		parent::__construct($attributes);
	}

	protected function _optionToHtml($option, $selected) {
		$html = '<input type="radio"' . $this->serialize(array('name', 'class', 'style'));
		if (is_array($option)) {
			$html.= 'value="' . $this->_escape($option['value']) . '"  id="' . $this->getHtmlId() . $option['value'] . '"';
			if ($option['value'] == $selected) {
				$html.= ' checked="checked"';
			}
			$html.= ' />';
			$html.= '<label class="inline" for="' . $this->getHtmlId() . $option['value'] . '">' . $option['label'] . '</label>';
		} elseif ($option instanceof Varien_Object && $selected) {
			$html.= 'id="' . $this->getHtmlId() . $option->getValue() . '"' . $option->serialize(array('label', 'title', 'value', 'class', 'style'));
			if (in_array($option->getValue(), $selected)) {
				$html.= ' checked="checked"';
			}
			$html.= ' />';
			$html.= '<label style = "margin-left:10px" class="inline" for="' . $this->getHtmlId() . $option->getValue() . '">' . $option->getLabel() . '</label>';
		} else {
			$html.= 'id="' . $this->getHtmlId() . $option->getValue() . '"' . $option->serialize(array('label', 'title', 'value', 'class', 'style'));
			$html.= ' />';
			$html.= '<label style = "margin-left:10px" class="inline" for="' . $this->getHtmlId() . $option->getValue() . '">' . $option->getLabel() . '</label>';
		}
		if ($option['additional_text']) {
			$html .= $option['additional_text'];
		}
		$html.= $this->getSeparator() . "\n";
		return $html;
	}

}

final class BS_Form extends Varien_Data_Form {

	private $_extraHtml = "";

	public function __construct($attributes=array()) {
		parent::__construct($attributes);
	}

	public function toHtml() {

		return parent::toHtml() . $this->_extraHtml;
	}

	public function setExtraHtml($html) {
		$this->_extraHtml = $html;
	}

}

class Brainsins_Recsins_Block_Adminhtml_Recsins_Edit_Form extends Mage_Adminhtml_Block_Widget_Form {

	//private $dev = true;
	private $dev = false;

	protected function _prepareForm() {

		Mage::getConfig()->cleanCache();

		$linkBaseUrl;

		if ($this->dev) {
			$linkBaseUrl = "http://dev-analytics.brainsins.com";
		} else {
			$linkBaseUrl = "http://analytics.brainsins.com";
		}

		$form = new BS_Form(array(
                    'id' => 'edit_form',
                    'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
                    'method' => 'post',
                    'enctype' => 'multipart/form-data'
		)
		);



		$data = $this->getRequest()->getPost();

		$key = Mage::getStoreConfig('brainsins/BSKEY');
		$key = $key ? $key : '';


		// print some non-visible html code to the page


		$js = "<script type='text/javascript'>" . PHP_EOL;
		$js .= "function bsImportConfig(){" . PHP_EOL;
		$js .= "var import_config = document.getElementById('import_config');" . PHP_EOL;
		$js .= "var edit_form = document.getElementById('edit_form');" . PHP_EOL;
		$js .= "import_config.value = '1';" . PHP_EOL;
		$js .= "edit_form.submit();" . PHP_EOL;
		$js .= "}" . PHP_EOL;
		$js.= "</script>" . PHP_EOL;
		echo($js);

		$style = "<style>";
		$style .= ".entry-edit .field-row label {width: 250px;}";

		$style .= "</style>";
		echo($style);

		// create form elements

		$clientsLinkUrl = "javascript:window.open('" . $linkBaseUrl . "/settings/accountinfo')";
		$nonClientsLinkUrl = "javascript:window.open('http://www.brainsins.es/tarifas')";


		$form->setUseContainer(true);
		$helper = $this->helper("recsins");

		$text = new Varien_Data_Form_Element_Text(array('name' => 'bskey_text'));
		$text->setId('bskey_text');
		$text->setValue($key);

		$keyQuestion = new Varien_Data_Form_Element_Label(array('value' => $helper->__('Do not have a BrainSINS KEY?')));
		$clientsLink = new Varien_Data_Form_Element_Link(array('value' => $helper->__('I am a BrainSINS client'), 'href' => $clientsLinkUrl, 'style' => 'padding-left:20px;'));
		$nonClientsLink = new Varien_Data_Form_Element_Link(array('value' => $helper->__('I am not a BrainSINS client yet'), 'href' => $nonClientsLinkUrl, 'style' => 'padding-left:20px;'));


		$enabledValue = Mage::getStoreConfig('brainsins/BS_ENABLED');

		if (!$enabledValue && $enabledValue !== '0') {
			Mage::getModel('core/config')->saveConfig('brainsins/BS_ENABLED', '1');
			$enabledValue = '1';
		}

		$enabledOptions = new BS_Radios(array('id' => 'bsenableoptions', 'name' => 'bsenableoptions', 'separator' => '<br><br>'));
		$enabledOption = new Varien_Data_Form_Element_Radio(array('id' => 'bsenabledoption', 'name' => 'bsenabledoption', 'label' => $helper->__("Enabled"), 'value' => '1'));
		$disabledOption = new Varien_Data_Form_Element_Radio(array('id' => 'bsdisabledoption', 'name' => 'bsdisabledoption', 'label' => $helper->__("Disabled"), 'value' => '0'));

		if ($enabledValue === '1') {
			$enabledOptions->setValue(array('1'));
		} else {
			$enabledOptions->setValue(array('0'));
		}

		$enabledOptions->setValues(array($enabledOption, $disabledOption));



		$keyFieldSet = new Varien_Data_Form_Element_Fieldset(array('name' => 'bskeyfs'));
		$keyFieldSet->setId('bskeyfs');
		$keyFieldSet->addElement(new Varien_Data_Form_Element_Label(array('value' => $helper->__('BrainSINS KEY'), 'bold' => true)));
		$keyFieldSet->addElement($text);
		$keyFieldSet->addElement($keyQuestion);
		$keyFieldSet->addElement($clientsLink);
		$keyFieldSet->addElement($nonClientsLink);

		$enableFieldSet = new Varien_Data_Form_Element_Fieldset(array('name' => 'bsenablefs'));
		$enableFieldSet->setId('bsenablefs');
		$enableFieldSet->addElement(new Varien_Data_Form_Element_Label(array('value' => $helper->__('Enable / Disable Extension'), 'bold' => true)));
		$enableFieldSet->addElement($enabledOptions);


		$model = Mage::getModel('recsins/recommender');
		$entries = $model->getAllRecommenders();
		$defaultText = $helper->__("Do not show");

		$defaultHomeOption = new Varien_Data_Form_Element_Radio();
		$defaultHomeOption->setLabel($defaultText);
		$defaultHomeOption->setValue(0);

		$defaultCategoryOption = new Varien_Data_Form_Element_Radio();
		$defaultCategoryOption->setLabel($defaultText);
		$defaultCategoryOption->setValue(0);

		$defaultProductOption = new Varien_Data_Form_Element_Radio();
		$defaultProductOption->setLabel($defaultText);
		$defaultProductOption->setValue(0);

		$defaultCartOption = new Varien_Data_Form_Element_Radio();
		$defaultCartOption->setLabel($defaultText);
		$defaultCartOption->setValue(0);

		$defaultCheckoutOption = new Varien_Data_Form_Element_Radio();
		$defaultCheckoutOption->setLabel($defaultText);
		$defaultCheckoutOption->setValue(0);


		$homeRadioOptions = array();
		$categoryRadioOptions = array();
		$productRadioOptions = array();
		$cartRadioOptions = array();
		$checkoutRadioOptions = array();

		$homeRadioOptions[] = $defaultHomeOption;
		$categoryRadioOptions[] = $defaultHomeOption;
		$productRadioOptions[] = $defaultProductOption;
		$cartRadioOptions[] = $defaultCartOption;
		$checkoutRadioOptions[] = $defaultCheckoutOption;

		$homeSelected = Mage::getStoreConfig('brainsins/BS_HOME_RECOMMENDER');
		$categorySelected = Mage::getStoreConfig('brainsins/BS_CATEGORY_RECOMMENDER');
		$productSelected = Mage::getStoreConfig('brainsins/BS_PRODUCT_RECOMMENDER');
		$cartSelected = Mage::getStoreConfig('brainsins/BS_CART_RECOMMENDER');
		$checkoutSelected = Mage::getStoreConfig('brainsins/BS_CHECKOUT_RECOMMENDER');

		$outOfStockSelected = Mage::getStoreConfig('brainsins/BS_SEND_OUT_OF_STOCK_PRODUCTS');



		foreach ($entries as $entry) {
			$id = $entry['id'];
			$name = $entry['name'];
			$page = $entry['page'];

			$radio = new Varien_Data_Form_Element_Radio();
			$radioLink = "javascript:window.open(\"" . $linkBaseUrl . "/optimization/editrecommenderstyle?idRecommender=" . $id . "\")";
			$radio->setLabel($name);
			$radio->setValue($id);
			$radio->setAdditional_text("<input type='button' onclick='" . $radioLink . "' value='" . $helper->__("Edit Style") . "' style='margin-left:20px'>");

			switch ($page) {
				case 1:
					$optionsHome[$id] = $name;
					$homeRadioOptions[] = $radio;
					$optionsCategory[$id] = $name;
					$categoryRadioOptions[] = $radio;
					break;
				case 2:
					$optionsProduct[$id] = $name;
					$productRadioOptions[] = $radio;
					break;
				case 3:
					$optionsCart[$id] = $name;
					$cartRadioOptions[] = $radio;
					break;
				case 4:
					$checkoutRadioOptions[] = $radio;
					$optionsCheckout[$id] = $name;
					break;
			}
		}

		// home

		$homeRecommenders = new BS_Radios(array('name' => 'bshome_recommenders', 'separator' => '<br><br>'));
		$homeRecommenders->setId('bshome_recommenders');
		$homeRecommenders->setValues($homeRadioOptions);
		if ($homeSelected !== null) {
			$homeRecommenders->setValue(array($homeSelected));
		} else {
			$homeRecommenders->setValue(array(0));
		}

		$homeFieldSet = new Varien_Data_Form_Element_Fieldset(array('name' => 'bshomerecommendersfs'));
		$homeFieldSet->setId('bshomerecommendersfs');
		$homeFieldSet->addElement(new Varien_Data_Form_Element_Label(array('value' => $helper->__('Home Page Recommender'), 'bold' => true)));
		$homeFieldSet->addElement($homeRecommenders);


		// category

		$categoryRecommenders = new BS_Radios(array('name' => 'bscategory_recommenders', 'separator' => '<br><br>'));
		$categoryRecommenders->setId('bscategory_recommenders');
		$categoryRecommenders->setValues($categoryRadioOptions);
		if ($categorySelected !== null) {
			$categoryRecommenders->setValue(array($categorySelected));
		} else {
			$categoryRecommenders->setValue(array(0));
		}

		$categoryFieldSet = new Varien_Data_Form_Element_Fieldset(array('name' => 'bscategoryrecommendersfs'));
		$categoryFieldSet->setId('bscategoryrecommendersfs');
		$categoryFieldSet->addElement(new Varien_Data_Form_Element_Label(array('value' => $helper->__('Category Page Recommender'), 'bold' => true)));
		$categoryFieldSet->addElement($categoryRecommenders);

		// product

		$productRecommenders = new BS_Radios(array('name' => 'bsproduct_recommenders', 'separator' => '<br><br>'));
		$productRecommenders->setId('bsproduct_recommenders');
		$productRecommenders->setValues($productRadioOptions);
		if ($productSelected !== null) {
			$productRecommenders->setValue(array($productSelected));
		} else {
			$productRecommenders->setValue(array(0));
		}

		$productFieldSet = new Varien_Data_Form_Element_Fieldset(array('name' => 'bsproductrecommendersfs'));
		$productFieldSet->setId('bsproductrecommendersfs');
		$productFieldSet->addElement(new Varien_Data_Form_Element_Label(array('value' => $helper->__('Product Page Recommender'), 'bold' => true)));
		$productFieldSet->addElement($productRecommenders);

		// cart

		$cartRecommenders = new BS_Radios(array('name' => 'bscart_recommenders', 'separator' => '<br><br>'));
		$cartRecommenders->setId('bscart_recommenders');
		$cartRecommenders->setValues($cartRadioOptions);
		if ($cartSelected !== null) {
			$cartRecommenders->setValue(array($cartSelected));
		} else {
			$cartRecommenders->setValue(array(0));
		}

		$cartFieldSet = new Varien_Data_Form_Element_Fieldset(array('name' => 'bscartrecommendersfs'));
		$cartFieldSet->setId('bscartrecommendersfs');
		$cartFieldSet->addElement(new Varien_Data_Form_Element_Label(array('value' => $helper->__('Cart Page Recommender'), 'bold' => true)));
		$cartFieldSet->addElement($cartRecommenders);

		// checkout

		$checkoutRecommenders = new BS_Radios(array('name' => 'bscheckout_recommenders', 'separator' => '<br><br>'));
		$checkoutRecommenders->setId('bscheckout_recommenders');
		$checkoutRecommenders->setValues($checkoutRadioOptions);
		if ($checkoutSelected !== null) {
			$checkoutRecommenders->setValue(array($checkoutSelected));
		} else {
			$checkoutRecommenders->setValue(array(0));
		}

		$checkoutFieldSet = new Varien_Data_Form_Element_Fieldset(array('name' => 'bscheckoutrecommendersfs'));
		$checkoutFieldSet->setId('bscheckoutrecommendersfs');
		$checkoutFieldSet->addElement(new Varien_Data_Form_Element_Label(array('value' => $helper->__('Checkout Page Recommender'), 'bold' => true)));
		$checkoutFieldSet->addElement($checkoutRecommenders);


		$advancedFieldSet = new Varien_Data_Form_Element_Fieldset(array('name' => 'bsadvancedoptionsfs'));
		$advancedFieldSet->setId('bsadvancedoptionsfs');
		$advancedFieldSet->addElement(new Varien_Data_Form_Element_Label(array('value' => $helper->__('Advanced Options'), 'bold' => true)));

		$cautionText = "(" . $helper->__("please do not modify these values unless you are told so by BrainSINS' support team") . ")";

		//$advancedFieldSet->addElement(new Varien_Data_Form_Element_Label(array('value' => $cautionText)));
		//$advancedFieldSet->addElement(new Varien_Data_Form_Element_Label(array('value' => $helper->__("Catalog upload page size"), 'bold' => 'true')));

		/*$radio1 = new Varien_Data_Form_Element_Radio();
		 $radio1->setLabel("1");
		$radio1->setValue("page1");
		$radio1->setAdditional_text("");

		$radio10 = new Varien_Data_Form_Element_Radio();
		$radio10->setLabel("10");
		$radio10->setValue("page10");
		$radio10->setAdditional_text("");

		$radio20 = new Varien_Data_Form_Element_Radio();
		$radio20->setLabel("20");
		$radio20->setValue("page20");
		$radio20->setAdditional_text("");

		$radio50 = new Varien_Data_Form_Element_Radio();
		$radio50->setLabel("50");
		$radio50->setValue("page50");
		$radio50->setAdditional_text("");

		$radio100 = new Varien_Data_Form_Element_Radio();
		$radio100->setLabel("100");
		$radio100->setValue("page100");
		$radio100->setAdditional_text("");

		$radio200 = new Varien_Data_Form_Element_Radio();
		$radio200->setLabel("200");
		$radio200->setValue("page200");
		$radio200->setAdditional_text("");

		$advancedOptions = array();
		$advancedOptions[] = $radio1;
		$advancedOptions[] = $radio10;
		$advancedOptions[] = $radio20;
		$advancedOptions[] = $radio50;
		$advancedOptions[] = $radio100;
		$advancedOptions[] = $radio200;

		$advancedRadios = new BS_Radios(array('name' => 'bsadvanced', 'separator' => '<br>'));
		$advancedRadios->setId("bsadvanced_options");
		$advancedRadios->setValues($advancedOptions);

		$pageSize = Mage::getStoreConfig('brainsins/BS_PAGE_SIZE');

		if (!isset($pageSize) || !($pageSize != 'page1' || $pageSize != 'page10' || $pageSize != 'page20' || $pageSize != 'page50' || $pageSize != 'page100' || $pageSize != 'page200')) {
		$pageSize = 'page50';
		}

		$advancedRadios->setValue(array($pageSize));
		*/
		$includeOutOfStockLabel = new Varien_Data_Form_Element_Label(array('value' => $helper->__("Include out of stock products in catalog upload (out of stock products will be recommended)"), 'bold' => 'true'));

		$outOfStockCheckbox = new Varien_Data_Form_Element_Checkbox(array('name' => 'out_of_stock_checkbox'));
		$outOfStockCheckbox->setId('out_of_stock_checkbox');
		$outOfStockCheckbox->setValue("checked");
		if (isset($outOfStockSelected) && $outOfStockSelected != null) {
			if ($outOfStockSelected == "1") {
				$outOfStockCheckbox->setIsChecked(true);
			}
		} else {
			//$outOfStockCheckbox->setIsChecked(true);
		}

		$imagesLabel = new Varien_Data_Form_Element_Label(array('value' => $helper->__("Select Image url type"), 'bold' => 'true'));

		$radioNotResize = new Varien_Data_Form_Element_Radio();
		$radioNotResize->setLabel($helper->__("Default value for product ''small'' image"));
		$radioNotResize->setValue("image_no_resize");
		$radioNotResize->setAdditional_text("");

		$radioResize = new Varien_Data_Form_Element_Radio();
		$radioResize->setLabel($helper->__("Resize product ''small'' image"));
		$radioResize->setValue("image_resize");
		$radioResize->setAdditional_text("");

		$imageOptions = array();
		$imageOptions[] = $radioNotResize;
		$imageOptions[] = $radioResize;
		$imageRadios = new BS_Radios(array('name' => 'bsimageoptions', 'separator' => '<br>'));
		$imageRadios->setId("image_options");
		$imageRadios->setValues($imageOptions);

		$widthText = new Varien_Data_Form_Element_Text(array('name' => 'image_width_text', "label" => $helper->__('Width')));
		$heigthText = new Varien_Data_Form_Element_Text(array('name' => 'image_heigth_text', "label" => $helper->__("Heigth")));

		$imageSelectedOption = Mage::getStoreConfig('brainsins/BS_IMAGE_RESIZE');
		if (!isset($imageSelectedOption) || !($imageSelectedOption == 'image_no_resize' || $imageSelectedOption == 'image_resize')) {
			$imageSelectedOption = 'image_no_resize';
		}

		$imageRadios->setValue(array($imageSelectedOption));


		$imageSelectedWidth = Mage::getStoreConfig('brainsins/BS_IMAGE_RESIZE_WIDTH');
		$imageSelectedHeigth = Mage::getStoreConfig('brainsins/BS_IMAGE_RESIZE_HEIGTH');

		if (isset($imageSelectedWidth) && is_numeric($imageSelectedWidth) && $imageSelectedWidth > 0) {
			$widthText->setValue($imageSelectedWidth);
		}

		if (isset($imageSelectedHeigth) && is_numeric($imageSelectedHeigth) && $imageSelectedHeigth > 0) {
			$heigthText->setValue($imageSelectedHeigth);
		}


		$specialPriceLabel = new Varien_Data_Form_Element_Label(array('value' => $helper->__("Upload special price"), 'bold' => 'true'));

		$specialPriceYes = new Varien_Data_Form_Element_Radio();
		$specialPriceYes->setLabel($helper->__("Yes"));
		$specialPriceYes->setValue("special_price_yes");
		$specialPriceYes->setAdditional_text("");

		$specialPriceNo = new Varien_Data_Form_Element_Radio();
		$specialPriceNo->setLabel($helper->__("No"));
		$specialPriceNo->setValue("special_price_no");
		$specialPriceNo->setAdditional_text("");

		$specialPriceOptions = array();
		$specialPriceOptions[] = $specialPriceYes;
		$specialPriceOptions[] = $specialPriceNo;
		$specialPriceRadios = new BS_Radios(array('name' => 'bsspecialpriceoptions', 'separator' => '<br>'));
		$specialPriceRadios->setId("special_price_options");
		$specialPriceRadios->setValues($specialPriceOptions);

		$specialPriceSelectedOption = Mage::getStoreConfig('brainsins/BS_USE_SPECIAL_PRICE');
		if (isset($specialPriceSelectedOption) && $specialPriceSelectedOption == '1') {
			$specialPriceSelectedOption = 'special_price_yes';
		} else if (!isset($spacialPriceSelectedOption)) {
			$specialPriceSelectedOption = 'special_price_yes';
		} else {
			$specialPriceSelectedOption = 'special_price_no';
		}

		$specialPriceRadios->setValue(array($specialPriceSelectedOption));

		$taxPriceLabel = new Varien_Data_Form_Element_Label(array('value' => $helper->__("Tax Prices"), 'bold' => 'true'));

		$taxPriceEqual = new Varien_Data_Form_Element_Radio();
		$taxPriceEqual->setLabel($helper->__("Display Prices are equal to Catalog Prices"));
		$taxPriceEqual->setValue("tax_price_equal");
		$taxPriceEqual->setAdditional_text("");

		$taxPriceInDisplay = new Varien_Data_Form_Element_Radio();
		$taxPriceInDisplay->setLabel($helper->__("Display Prices include taxes and Catalog Prices do not include taxes"));
		$taxPriceInDisplay->setValue("tax_price_plus");
		$taxPriceInDisplay->setAdditional_text("");

		$taxPriceInCatalog = new Varien_Data_Form_Element_Radio();
		$taxPriceInCatalog->setLabel($helper->__("Display Prices do not include taxes and Catalog Prices include taxes"));
		$taxPriceInCatalog->setValue("tax_price_minus");
		$taxPriceInCatalog->setAdditional_text("");

		$taxPriceOptions = array();
		$taxPriceOptions[] = $taxPriceEqual;
		$taxPriceOptions[] = $taxPriceInDisplay;
		$taxPriceOptions[] = $taxPriceInCatalog;

		$taxPriceRadios = new BS_Radios(array('name' => 'bstaxpriceoptions', 'separator' => '<br>'));
		$taxPriceRadios->setId("tax_price_options");
		$taxPriceRadios->setValues($taxPriceOptions);

		$taxPriceSelectedOption = Mage::getStoreConfig('brainsins/BS_TAX_PRICE');
		$taxPriceDisplayOption;
		if (isset($taxPriceSelectedOption) && ($taxPriceSelectedOption == 'tax_price_equal' || $taxPriceSelectedOption == 'tax_price_plus' || $taxPriceSelectedOption == 'tax_price_minus')) {
			$taxPriceDisplayOption = $taxPriceSelectedOption;
		} else {
			$taxPriceDisplayOption = 'tax_price_equal';
		}

		$taxPriceRadios->setValue(array($taxPriceDisplayOption));


		$importScriptLabel = new Varien_Data_Form_Element_Label(array('value' => $helper->__("Custom style script (HTTP)"), 'bold' => 'true'));
		$scriptUrl = Mage::getStoreConfig('brainsins/BS_SCRIPT_URL');
		$importScript = new Varien_Data_Form_Element_Text(array('name' => 'bs_script', 'style' => 'width:400px'));
		$importScript->setId('bs_script');
		$importScript->setValue($scriptUrl);

		$importScriptHttpsLabel = new Varien_Data_Form_Element_Label(array('value' => $helper->__("Custom style script (HTTPS)"), 'bold' => 'true'));
		$scriptHttpsUrl = Mage::getStoreConfig('brainsins/BS_SCRIPT_THTTPS_URL');
		$importScriptHttps = new Varien_Data_Form_Element_Text(array('name' => 'bs_script_https', 'style' => 'width:400px'));
		$importScriptHttps->setId('bs_script_https');
		$importScriptHttps->setValue($scriptHttpsUrl);

		$useHighDetail = new Varien_Data_Form_Element_Checkbox(array('name' => 'use_high_detail'));
		$useHighDetail->setId('use_high_detail');
		$useHighDetail->setValue("checked");
		$useHighDetailOption = Mage::getStoreConfig('brainsins/BS_USE_HIGH_DETAIL');
		if (isset($useHighDetailOption) && $useHighDetailOption != null) {
			if ($useHighDetailOption == "1") {
				$useHighDetail->setIsChecked(true);
			} else {
				$useHighDetail->setIsChecked(false);
			}
		} else {
			$useHighDetail->setIsChecked(false);
		}
		$useHighDetailLabel = new Varien_Data_Form_Element_Label(array('value' => $helper->__("Use high detail in recommendation requests"), 'bold' => 'true'));

		$useAjaxRequests = new Varien_Data_Form_Element_Checkbox(array('name' => 'use_ajax_requests'));
		$useAjaxRequests->setId('use_ajax_requests');
		$useAjaxRequests->setValue("checked");
		$useAjaxRequestsOption = Mage::getStoreConfig('brainsins/BS_USE_AJAX_REQUESTS');
		if (isset($useAjaxRequestsOption) && $useAjaxRequestsOption != null) {
			if ($useAjaxRequestsOption == "1") {
				$useAjaxRequests->setIsChecked(true);
			} else {
				$useAjaxRequests->setIsChecked(false);
			}
		} else {
			$useAjaxRequests->setIsChecked(false);
		}
		$useAjaxRequestsLabel = new Varien_Data_Form_Element_Label(array('value' => $helper->__("Use ajax for recommendation requests"), 'bold' => 'true'));
		

		//$advancedFieldSet->addElement($advancedRadios);
		$advancedFieldSet->addElement($includeOutOfStockLabel);
		$advancedFieldSet->addElement($outOfStockCheckbox);
		$advancedFieldSet->addElement($imagesLabel);
		$advancedFieldSet->addElement($imageRadios);
		$advancedFieldSet->addElement($widthText);
		$advancedFieldSet->addElement($heigthText);
		$advancedFieldSet->addElement($specialPriceLabel);
		$advancedFieldSet->addElement($specialPriceRadios);
		$advancedFieldSet->addElement($taxPriceLabel);
		$advancedFieldSet->addElement($taxPriceRadios);
		$advancedFieldSet->addElement($importScriptLabel);
		$advancedFieldSet->addElement($importScript);
		$advancedFieldSet->addElement($importScriptHttpsLabel);
		$advancedFieldSet->addElement($importScriptHttps);
		$advancedFieldSet->addElement($useHighDetailLabel);
		$advancedFieldSet->addElement($useHighDetail);
		$advancedFieldSet->addElement($useAjaxRequestsLabel);
		$advancedFieldSet->addElement($useAjaxRequests);

		//$advancedFieldSet->addElement($imagesExplainLabel1);
		//$advancedFieldSet->addElement($imagesExplainLabel2);
		// buttons

		$hiddenImport = new Varien_Data_Form_Element_Hidden(array('name' => 'import_config'));
		$hiddenImport->setId("import_config");
		$hiddenImport->setValue("0");


		$importButton = new Varien_Data_Form_Element_Button(array('name' => 'import_config_button'));
		$importButton->setId('import_config_button');
		$importButton->setValue($helper->__('Import Recommenders'));
		$importButton->setOnclick("bsImportConfig()");





		// build the final form

		$form->addElement($keyFieldSet);
		$form->addElement($enableFieldSet);
		$form->addElement($homeFieldSet);
		$form->addElement($categoryFieldSet);
		$form->addElement($productFieldSet);
		$form->addElement($cartFieldSet);
		$form->addElement($checkoutFieldSet);
		$form->addElement($advancedFieldSet);
		$form->addElement($hiddenImport);
		//$form->addElement($hiddenUpload);
		$form->addElement($importButton);

		/*if (isset($uploading) && $uploading == '1') {
		 $form->addElement($abortButton);
		} else {
		$form->addElement($uploadButton);
		}*/
		$this->setForm($form);

		return $form;
	}

}
