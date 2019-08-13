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

function bsErrorHandler($errno, $errstr, $errfile, $errline) {
	//Mage::getModel('recsins/recsins')->log($errno . " | " . $errstr . " | " . $errfile . " at line " . $errline);
	Mage::getSingleton('adminhtml/session')->addError(Mage::helper('recsins')->__('Error. Please try again'));
	return mageCoreErrorHandler($errno, $errstr, $errfile, $errline);
}

class Brainsins_Recsins_Adminhtml_RecsinsController extends Mage_Adminhtml_Controller_Action {

	protected function _initAction() {
		$this->loadLayout()
		->_setActiveMenu('recsins/items')
		->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));

		return $this;
	}

	public function indexAction() {
		//TODO just for testing
		$currentVersion = Mage::getStoreConfig('brainsins/BS_VERSION');
		$lastVersion = Mage::getStoreConfig('brainsins/BS_LAST_AVAILABLE_VERSION');
		if (isset($lastVersion) && isset($currentVersion)) {
			if ($lastVersion > $currentVersion) {
				Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('recsins')->__('There is a new Version Available: BrainSINS extension for Magento v') . $lastVersion);
			}
		}

		$this->_initAction()
		->renderLayout();
	}

	public function editAction() {
		$id = $this->getRequest()->getParam('id');
		$model = Mage::getModel('recsins/recsins')->load($id);

		if ($model->getId() || $id == 0) {
			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
			if (!empty($data)) {
				$model->setData($data);
			}

			Mage::register('recsins_data', $model);

			$this->loadLayout();
			$this->_setActiveMenu('recsins/items');

			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item Manager'), Mage::helper('adminhtml')->__('Item Manager'));
			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item News'), Mage::helper('adminhtml')->__('Item News'));

			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

			$this->_addContent($this->getLayout()->createBlock('recsins/adminhtml_recsins_edit'))
			->_addLeft($this->getLayout()->createBlock('recsins/adminhtml_recsins_edit_tabs'));

			$this->renderLayout();
		} else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('recsins')->__('Item does not exist'));
			$this->_redirect('*/*/');
		}
	}

	public function newAction() {
		$this->_forward('edit');
	}

	private function validateBSKey($key) {
		$pattern = '/^BS-\d{10}-\d+$/';
		if (preg_match($pattern, $key)) {
			return true;
		} else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('recsins')->__('"' . $key . '" is not a valid BS-KEY. The BS-KEY must be like "BS-0123456789-1"'));
			return false;
		}
	}

	public function saveAction() {

		$old_error_handler = set_error_handler("bsErrorHandler");

		try {

			//throw new Exception("BSException: extrange category");

			$data = $this->getRequest()->getPost();

			if ($data) {

				$key = $data['bskey_text'];

				if (!$this->validateBSKey($key)) {
					$this->_redirect('*/*/');
					return;
				}

				if (isset($key)) {
					Mage::getModel('core/config')->saveConfig('brainsins/BSKEY', $key);
				}

				$enabled = $data['bsenableoptions'];

				if ($enabled === '0') {
					Mage::getModel('core/config')->saveConfig('brainsins/BS_ENABLED', '0');
				} else {
					Mage::getModel('core/config')->saveConfig('brainsins/BS_ENABLED', '1');
				}


				$homeRecommender;
				$categoryRecommender;
				$productRecommender;
				$cartRecommender;
				$checkoutRecommender;

				if (array_key_exists('bshome_recommenders', $data)) {
					$homeRecommender = $data['bshome_recommenders'];
				} else {
					$homeRecommender = 0;
				}

				if (array_key_exists('bscategory_recommenders', $data)) {
					$categoryRecommender = $data['bscategory_recommenders'];
				} else {
					$categoryRecommender = 0;
				}

				if (array_key_exists('bsproduct_recommenders', $data)) {
					$productRecommender = $data['bsproduct_recommenders'];
				} else {
					$productRecommender = 0;
				}

				if (array_key_exists('bscart_recommenders', $data)) {
					$cartRecommender = $data['bscart_recommenders'];
				} else {
					$cartRecommender = 0;
				}

				if (array_key_exists('bscheckout_recommenders', $data)) {
					$checkoutRecommender = $data['bscheckout_recommenders'];
				} else {
					$checkoutRecommender = 0;
				}

				if (isset($homeRecommender) && $homeRecommender !== null) {
					Mage::getModel('core/config')->saveConfig('brainsins/BS_HOME_RECOMMENDER', $homeRecommender);
				}

				if (isset($categoryRecommender) && $categoryRecommender !== null) {
					Mage::getModel('core/config')->saveConfig('brainsins/BS_CATEGORY_RECOMMENDER', $categoryRecommender);
				}

				if (isset($productRecommender) && $productRecommender !== null) {
					Mage::getModel('core/config')->saveConfig('brainsins/BS_PRODUCT_RECOMMENDER', $productRecommender);
				}

				if (isset($checkoutRecommender) && $checkoutRecommender !== null) {
					Mage::getModel('core/config')->saveConfig('brainsins/BS_CHECKOUT_RECOMMENDER', $checkoutRecommender);
				}

				if (isset($cartRecommender) && $cartRecommender !== null) {
					Mage::getModel('core/config')->saveConfig('brainsins/BS_CART_RECOMMENDER', $cartRecommender);
				}

				$uploadOutOfStockProducts = false;

				if (array_key_exists("out_of_stock_checkbox", $data)) {
					$uploadOutOfStockValue = $data['out_of_stock_checkbox'];
					if (isset($uploadOutOfStockValue) && $uploadOutOfStockValue == "checked") {
						Mage::getModel('core/config')->saveConfig('brainsins/BS_SEND_OUT_OF_STOCK_PRODUCTS', "1");
						Mage::getSingleton("core/session", array("name" => "adminhtml"))->setData('brainsins_BS_SEND_OUT_OF_STOCK_PRODUCTS', "1");
					} else {
						Mage::getModel('core/config')->saveConfig('brainsins/BS_SEND_OUT_OF_STOCK_PRODUCTS', "0");
						Mage::getSingleton("core/session", array("name" => "adminhtml"))->setData('brainsins_BS_SEND_OUT_OF_STOCK_PRODUCTS', "0");
					}
				} else {
					Mage::getModel('core/config')->saveConfig('brainsins/BS_SEND_OUT_OF_STOCK_PRODUCTS', "0");
					Mage::getSingleton("core/session", array("name" => "adminhtml"))->setData('brainsins_BS_SEND_OUT_OF_STOCK_PRODUCTS', "0");
				}

				if (array_key_exists("bsimageoptions", $data)) {
					$resizeImages = $data['bsimageoptions'];
					if (isset($resizeImages)) {
						if ($resizeImages == 'image_no_resize') {
							Mage::getModel('core/config')->saveConfig('brainsins/BS_IMAGE_RESIZE', "image_no_resize");
							Mage::getSingleton("core/session", array("name" => "adminhtml"))->setData('brainsins_BS_IMAGE_RESIZE', "image_no_resize");
						} else if ($resizeImages == 'image_resize') {
							Mage::getModel('core/config')->saveConfig('brainsins/BS_IMAGE_RESIZE', "image_resize");
							Mage::getSingleton("core/session", array("name" => "adminhtml"))->setData('brainsins_BS_IMAGE_RESIZE', "image_resize");
							if (array_key_exists('image_width_text', $data) && isset($data['image_width_text'])) {
								$width = $data['image_width_text'];
								if (is_numeric($width) && $width > 0) {
									Mage::getModel('core/config')->saveConfig('brainsins/BS_IMAGE_RESIZE_WIDTH', $width);
									Mage::getSingleton("core/session", array("name" => "adminhtml"))->setData('brainsins_BS_IMAGE_RESIZE_WIDTH', $width);
								}
							}

							if (array_key_exists('image_heigth_text', $data) && isset($data['image_heigth_text'])) {
								$heigth = $data['image_heigth_text'];
								if (is_numeric($heigth) && $heigth > 0) {
									Mage::getModel('core/config')->saveConfig('brainsins/BS_IMAGE_RESIZE_HEIGTH', $heigth);
									Mage::getSingleton("core/session", array("name" => "adminhtml"))->setData('brainsins_BS_IMAGE_RESIZE_HEIGTH', $heigth);
								} else {
									Mage::getModel('core/config')->saveConfig('brainsins/BS_IMAGE_RESIZE_HEIGTH', "");
									Mage::getSingleton("core/session", array("name" => "adminhtml"))->setData('brainsins_BS_IMAGE_RESIZE_HEIGTH', "");
								}
							}
						} else {
							Mage::getModel('core/config')->saveConfig('brainsins/BS_IMAGE_RESIZE', "image_no_resize");
							Mage::getSingleton("core/session", array("name" => "adminhtml"))->setData('brainsins_BS_IMAGE_RESIZE', "image_no_resize");
						}
					}
				}

				if (array_key_exists("bsspecialpriceoptions", $data)) {
					$specialPrice = $data['bsspecialpriceoptions'];
					if (isset($specialPrice)) {
						if ($specialPrice == 'special_price_yes') {
							Mage::getModel('core/config')->saveConfig('brainsins/BS_USE_SPECIAL_PRICE', "1");
							Mage::getSingleton("core/session", array("name" => "adminhtml"))->setData('brainsins/BS_USE_SPECIAL_PRICE', "1");
						} else {
							Mage::getModel('core/config')->saveConfig('brainsins/BS_USE_SPECIAL_PRICE', "0");
							Mage::getSingleton("core/session", array("name" => "adminhtml"))->setData('brainsins/BS_USE_SPECIAL_PRICE', "0");
						}
					}
				}

				if (array_key_exists("bstaxpriceoptions", $data)) {
					$taxPrice = $data['bstaxpriceoptions'];
					if (isset($taxPrice)) {
						if ($taxPrice == 'tax_price_equal') {
							Mage::getModel('core/config')->saveConfig('brainsins/BS_TAX_PRICE', "tax_price_equal");
						} else if ($taxPrice == 'tax_price_plus') {
							Mage::getModel('core/config')->saveConfig('brainsins/BS_TAX_PRICE', "tax_price_plus");
						} else if ($taxPrice == 'tax_price_minus') {
							Mage::getModel('core/config')->saveConfig('brainsins/BS_TAX_PRICE', "tax_price_minus");
						} else {
							Mage::getModel('core/config')->saveConfig('brainsins/BS_TAX_PRICE', "tax_price_equal");
						}
					}
				}
				
				if (array_key_exists("bs_script", $data)) {
					$bsScript = $data['bs_script'];
					if (isset($bsScript)) {
						Mage::getModel('core/config')->saveConfig('brainsins/BS_SCRIPT_URL', $bsScript);
					}
				}
				
				if (array_key_exists("bs_script_https", $data)) {
					$bsScriptHttps = $data['bs_script_https'];
					if (isset($bsScriptHttps)) {
						Mage::getModel('core/config')->saveConfig('brainsins/BS_SCRIPT_THTTPS_URL', $bsScriptHttps);
					}
				}
				
				if (array_key_exists("use_high_detail", $data)) {					
					$useHighDetailValue = $data['use_high_detail'];
					if (isset($useHighDetailValue) && $useHighDetailValue == "checked") {
						Mage::getModel('core/config')->saveConfig('brainsins/BS_USE_HIGH_DETAIL', "1");
					} else {
						Mage::getModel('core/config')->saveConfig('brainsins/BS_USE_HIGH_DETAIL', "0");
					}
				} else {
					Mage::getModel('core/config')->saveConfig('brainsins/BS_USE_HIGH_DETAIL', "0");
				}
				
				if (array_key_exists("use_ajax_requests", $data)) {
					$useAjaxRequestsValue = $data['use_ajax_requests'];
					if (isset($useAjaxRequestsValue) && $useAjaxRequestsValue == "checked") {
						Mage::getModel('core/config')->saveConfig('brainsins/BS_USE_AJAX_REQUESTS', "1");
					} else {
						Mage::getModel('core/config')->saveConfig('brainsins/BS_USE_AJAX_REQUESTS', "0");
					}
				} else {
					Mage::getModel('core/config')->saveConfig('brainsins/BS_USE_AJAX_REQUESTS', "0");
				}

				$importConfig = $data['import_config'];
				if (isset($importConfig) && $importConfig == '1') {

					$recsins = Mage::getSingleton('recsins/recsins');
					$result = $recsins->importRecommenders();
					if ($result === true) {
						Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('recsins')->__('Recommenders imported sucssessfully'));
					} else {
						Mage::getSingleton('adminhtml/session')->addError(Mage::helper('recsins')->__('Error while importing recommenders'));
					}
					$this->_redirect('*/*/');
				}
			}
		} catch (Exception $e) {
			Mage::getModel('recsins/recsins')->log($e);
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('recsins')->__('Error. Please try again'));
		}

		$this->_redirect('*/*/');


		return;
	}

	public function deleteAction() {
		$this->_redirect('*/*/');
	}

	public function massDeleteAction() {
		$this->_redirect('*/*/index');
	}

	public function massStatusAction() {
		$this->_redirect('*/*/index');
	}

	public function exportCsvAction() {

	}

	public function exportXmlAction() {

	}

	protected function _sendUploadResponse($fileName, $content, $contentType='application/octet-stream') {
		$response = $this->getResponse();
		$response->setHeader('HTTP/1.1 200 OK', '');
		$response->setHeader('Pragma', 'public', true);
		$response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
		$response->setHeader('Content-Disposition', 'attachment; filename=' . $fileName);
		$response->setHeader('Last-Modified', date('r'));
		$response->setHeader('Accept-Ranges', 'bytes');
		$response->setHeader('Content-Length', strlen($content));
		$response->setHeader('Content-type', $contentType);
		$response->setBody($content);
		$response->sendResponse();
		die;
	}

}
