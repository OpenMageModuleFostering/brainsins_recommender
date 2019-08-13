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

class Brainsins_Recommender_Block_Adminhtml_System_Config_Source_Recommenders_Home extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	protected $_addRowButtonHtml = array();
	protected $_removeRowButtonHtml = array();

	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
	{
		$this->setElement($element);
 		$html = '<div id="brainsins_recommender_recommenders_home_template" style="display:none">';
		$html .= $this->_getRowTemplateHtml();
		$html .= '</div>';
 		$html .= '<ul id="brainsins_recommender_recommenders_container_home">';
		if ($this->_getValue('custom_div'))
		{
			foreach ($this->_getValue('custom_div') as $i => $f)
			{
               if ($i)
                   $html .= $this->_getRowTemplateHtml($i);
			}
		}
        $html .= "</ul>";
		$html .= $this->_getAddRowButtonHtml('brainsins_recommender_recommenders_container_home',
           'brainsins_recommender_recommenders_home_template', Mage::helper('brainsins_recommender')->__('Add new recommender'));

		return $html;
	}

	protected function _getRowTemplateHtml($rowIndex = 0)
	{
		$html = '<li>';
        $html .= '<div style="margin:5px 0 10px;">';

        if($this->getElement()->getData('value/'.'recommender_name/'.$rowIndex) != '')
        {
            $html .= $this->_getRecommenderIdInputBox($rowIndex, $this->getElement()->getData('value/'.'recommender_name/'.$rowIndex));
        }
        else
        {
			$html .= $this->_getRecommenderIdInputBox($rowIndex, '');
        }

       // $html.="<br/>";


		if($this->_getValue('custom_div/'.$rowIndex) != '')
			$class = 'custom-select-after-before-hide';
		else
			$class = 'custom-select-after-before';
		$html .= $this->_getBeforeAfterHtmlSelect($rowIndex, $this->getElement()->getData('value/'.'recommender_after_before/'.$rowIndex), 'recommender_after_before', $class);


        if($this->getElement()->getData('value/'.'recommender_position/'.$rowIndex) != '')
			$html .= $this->_getPositionsHtmlSelect($rowIndex, $this->getElement()->getData('value/'.'recommender_position/'.$rowIndex));
		else
			$html .= $this->_getPositionsHtmlSelect($rowIndex, 'content');



		$html .= '<p class="custom-p">';
		$html .= '<span class="custom-div"';
		if($this->_getValue('custom_div/'.$rowIndex) == '')
			$html .= ' style = "display:none;" ';
		$html .= '>'.Mage::helper('brainsins_recommender')->__('Custom pos:').' </span>';
		if($this->_getValue('custom_div/'.$rowIndex) == '')
			$class = 'custom-select-after-before-div-hide';
		else
			$class = 'custom-select-after-before-div';
		$html .= $this->_getCustomDivPositionsHtmlSelect($rowIndex, $this->getElement()->getData('value/'.'customdiv_after_before/'.$rowIndex), 'customdiv_after_before', $class).'<input type="text" class="custom-input-text" id="[custom_div]['.$rowIndex.']" name="'
			.$this->getElement()->getName().'[custom_div][]" placeholder="'.Mage::helper('brainsins_recommender')->__('Custom position').'" value="'
			.$this->_getValue('custom_div/'.$rowIndex) . '" ';
		if($this->_getValue('custom_div/'.$rowIndex) == '')
			$html .= ' style = "display:none;" ';
		$html .= $this->_getDisabled().'/>';
		$html .= $this->_getRemoveRowButtonHtml('li',Mage::helper('brainsins_recommender')->__('Delete'));
		$html .= '</p></div>';
		$html .= '<div class="recommender-separator"></div>';
		$html .= '</li>';

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

	protected function _getAddRowButtonHtml($container, $template, $title='Add')
	{
		if (!isset($this->_addRowButtonHtml[$container]))
		{
			$this->_addRowButtonHtml[$container] = $this->getLayout()->createBlock('adminhtml/widget_button')
					->setType('button')
					->setClass('add ' . $this->_getDisabled())
					->setLabel($this->__($title))
					->setOnClick("Element.insert($('" . $container . "'), {bottom: $('" . $template . "').innerHTML})")
					->setDisabled($this->_getDisabled())
					->toHtml();
		}
		return $this->_addRowButtonHtml[$container];
	}

	protected function _getRemoveRowButtonHtml($selector = 'li', $title = 'Delete')
	{
		if (!$this->_removeRowButtonHtml)
		{
			$this->_removeRowButtonHtml = $this->getLayout()->createBlock('adminhtml/widget_button')
				->setType('button')
				->setClass('delete v-middle custom' . $this->_getDisabled())
				->setLabel($this->__($title))
				->setOnClick("Element.remove($(this).up('" . $selector . "'))")
				->setDisabled($this->_getDisabled())
				->toHtml();
		}
		return $this->_removeRowButtonHtml;
	}

    protected function _getRecommenderIdInputBox($rowIndex, $value) {
        $name = $this->getElement()->getName().'[recommender_name][]';
        $id = '[recommender_name]['.$rowIndex.']';

        $html = "<div class='brainsins-recommenders-first-line'>";
        $html .= '<label for="' . $name . '" class="brainsins-recommenders-first-line-first-item">Recommender Id: </label>';
        $html .= '<input name="' . $name . '" id="' . $id . '" type="text" value="' . $value .'" class="brainsins-recommenders-first-line-second-item">';
        $html .= "</div>";
        return $html;
    }

	protected function _getRecommenderNamesHtmlSelect($rowIndex, $default_value = '')
	{
		$recommenders = Mage::helper('brainsins_recommender')->getRecommenders('1');

		$tmp = Mage::app()->getLayout()->createBlock('core/html_select')
		    ->setName($this->getElement()->getName().'[recommender_name][]'.$this->_getDisabled())
		    ->setId('[recommender_name]['.$rowIndex.']')
		    ->setTitle(Mage::helper('brainsins_recommender')->__('Recommender name'))
			->setValue($default_value)
		    ->setClass('custom-select-first required-recommender-home');
		$select = $tmp->setOptions($recommenders);

		return $select->getHtml();
	}

	protected function _getPositionsHtmlSelect($rowIndex, $default_value = '')
	{
		$positions = Mage::helper('brainsins_recommender')->getPositions('1');

		$tmp = Mage::app()->getLayout()->createBlock('core/html_select')
		    ->setName($this->getElement()->getName().'[recommender_position][]'.$this->_getDisabled())
		    ->setId('[recommender_position]['.$rowIndex.']')
		    ->setTitle(Mage::helper('brainsins_recommender')->__('Recommender position'))
			->setValue($default_value)
			->setExtraParams('onchange="checkCustom(this)"')
		    ->setClass('custom-select-last required-position-or-custom-div-home');
		$select = $tmp->setOptions($positions);

		return $select->getHtml();
	}

	protected function _getBeforeAfterHtmlSelect($rowIndex, $default_value = '', $default_id = '', $class)
	{
		$positions = array();
		$positions[] = array('value' => 'after', 'label' => Mage::helper('brainsins_recommender')->__('After'));
		$positions[] = array('value' => 'before', 'label' => Mage::helper('brainsins_recommender')->__('Before'));

		$tmp = Mage::app()->getLayout()->createBlock('core/html_select')
		    ->setName($this->getElement()->getName().'[recommender_after_before][]'.$this->_getDisabled())
		    ->setId('[recommender_after_before]['.$rowIndex.']')
			->setValue($default_value)
		    ->setClass($class);
		$select = $tmp->setOptions($positions);

		return $select->getHtml();
	}

	protected function _getCustomDivPositionsHtmlSelect($rowIndex, $default_value = '', $id, $class = '')
	{
		$positions = array();
		$positions[] = array('value' => 'replace', 'label' => Mage::helper('brainsins_recommender')->__('Replace'));
		$positions[] = array('value' => 'before', 'label' => Mage::helper('brainsins_recommender')->__('Before'));
		$positions[] = array('value' => 'first', 'label' => Mage::helper('brainsins_recommender')->__('First'));
		$positions[] = array('value' => 'last', 'label' => Mage::helper('brainsins_recommender')->__('Last'));
		$positions[] = array('value' => 'after', 'label' => Mage::helper('brainsins_recommender')->__('After'));

		$tmp = Mage::app()->getLayout()->createBlock('core/html_select')
		    ->setName($this->getElement()->getName().'['.$id.'][]'.$this->_getDisabled())
		    ->setId('['.$id.']['.$rowIndex.']')
			->setValue($default_value)
		    ->setClass($class);
		$select = $tmp->setOptions($positions);

		return $select->getHtml();
	}
}