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

class Brainsins_Recsins_Helper_Recsins extends Mage_Core_Helper_Abstract {

    public function getImageUrl($id) {
        if (isset($id) && $id) {
            $product = Mage::getModel("catalog/product")->load($id);
            if (isset($product)) {
                $imageSelectedOption = Mage::getStoreConfig('brainsins/BS_IMAGE_RESIZE');
                if (isset($imageSelectedOption) && $imageSelectedOption == 'image_resize') {

                    $width = 0;
                    $heigth = 0;

                    $imageSelectedWidth = Mage::getStoreConfig('brainsins/BS_IMAGE_RESIZE_WIDTH');
                    $imageSelectedHeigth = Mage::getStoreConfig('brainsins/BS_IMAGE_RESIZE_HEIGTH');

                    if (isset($imageSelectedWidth) && is_numeric($imageSelectedWidth) && $imageSelectedWidth > 0) {
                        $width = $imageSelectedWidth;
                        $url = "";

                        if (isset($imageSelectedHeigth) && is_numeric($imageSelectedHeigth) && $imageSelectedHeigth > 0) {
                            $heigth = $imageSelectedHeigth;
                            $url = (string) Mage::helper('catalog/image')->init($product, "small_image")->resize($width, $heigth);
                        } else {
                            $url = (string) Mage::helper('catalog/image')->init($product, "small_image")->resize($width);
                        }
                        return $url;
                    }
                }
                
                $prodImage = $product->getSmallImage();
                if (isset($prodImage) && $prodImage != null && $prodImage != "no_selection") {
                    return ((string) Mage::helper('catalog/image')->init($product, "small_image"));
                }
            }
        }
        return false;
    }
}