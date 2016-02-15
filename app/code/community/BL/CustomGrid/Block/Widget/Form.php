<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   BL
 * @package    BL_CustomGrid
 * @copyright  Copyright (c) 2016 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Widget_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Add the given suffix to the names of the fields contained in the given fieldset
     * This can be useful when there is a need for multiple suffixes in the same form
     * 
     * @param Varien_Data_Form_Element_Fieldset $fieldset Fieldset
     * @param string $suffix Field name suffix
     * @return BL_CustomGrid_Block_Widget_Form
     */
    protected function _addSuffixToFieldsetFieldNames(Varien_Data_Form_Element_Fieldset $fieldset, $suffix)
    {
        foreach ($fieldset->getElements() as $element) {
            $element->setName($fieldset->getForm()->addSuffixToName($element->getName(), $suffix));
        }
        return $this;
    }
    
    /**
     * Return the child name that should be used for the dependence block
     * 
     * @return string
     */
    public function getDependenceBlockName()
    {
        return 'form_after';
    }
    
    /**
     * Return the dependence block
     * 
     * @return BL_CustomGrid_Block_Widget_Form_Element_Dependence
     */
    public function getDependenceBlock()
    {
        if (!$dependenceBlock = $this->getChild($this->getDependenceBlockName())) {
            /** @var $dependenceBlock BL_CustomGrid_Block_Widget_Form_Element_Dependence */
            $dependenceBlock = $this->getLayout()->createBlock('customgrid/widget_form_element_dependence');
            $this->setChild($this->getDependenceBlockName(), $dependenceBlock);
        }
        return $dependenceBlock;
    }
    
    /**
     * Return the HTML code corresponding to an "Use config" checkbox applied to the given form element
     * 
     * @param Varien_Data_Form_Element_Abstract $element Form element
     * @return string
     */
    public function getUseConfigCheckboxHtml(Varien_Data_Form_Element_Abstract $element, $checkboxLabel = null)
    {
        $htmlId  = $element->getHtmlId() . '-uc-checkbox';
        $checked = $element->getDisabled();
        
        if (preg_match('#^([a-zA-Z_]+)(\[([a-zA-Z_]+)\])?(\[\])?$#', $element->getName(), $nameParts)) {
            $name = 'use_config[' . $nameParts[1] . ']';
            
            if (isset($nameParts[3]) && ($nameParts[3] !== '')) {
                $name .= '[' . $nameParts[3] . ']';
            }
        } else {
            $name = 'use_config[' . $element->getName() . ']';
        }
        
        return '<div class="blcg-use-config-wrapper">'
            . '<input type="checkbox" class="checkbox" id="' . $htmlId . '" ' . 'name="' . $name . '" value="1" '
            . ($checked ? 'checked="checked" ' : '')
            . 'onclick="toggleValueElements(this, Element.up(this.parentNode));" />'
            . '<label for="' . $htmlId . '">'
            . (is_null($checkboxLabel) ? $this->__('Use Config') : $checkboxLabel)
            . '</label>'
            . '</div>';
    }
    
    /**
     * Apply an "Use config" checkbox to the given form element
     * 
     * @param Varien_Data_Form_Element_Abstract $element Form element
     * @param bool $checked Whether the checkbox should initially be checked
     * @return BL_CustomGrid_Block_Widget_Form
     */
    public function applyUseConfigCheckboxToElement(
        Varien_Data_Form_Element_Abstract $element,
        $checked = false,
        $checkboxLabel = null
    ) {
        if ($checked) {
            $element->setDisabled(true);
            $element->addClass('disabled');
        }
        
        $element->setAfterElementHtml(
            $this->getUseConfigCheckboxHtml($element, $checkboxLabel) . $element->getAfterElementHtml()
        );
        
        return $this;
    }
    
    /**
     * Return the current grid model
     * 
     * @return BL_CustomGrid_Model_Grid
     */
    public function getGridModel()
    {
        return $this->getDataSetDefault('grid_model', Mage::registry('blcg_grid'));
    }
    
    /**
     * Return the current grid profile
     * 
     * @return BL_CustomGrid_Model_Grid_Profile
     */
    public function getGridProfile()
    {
        return $this->getDataSetDefault('grid_profile', Mage::registry('blcg_grid_profile'));
    }
    
    /**
     * Return the current custom column
     * 
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    public function getCustomColumn()
    {
        return Mage::registry('blcg_custom_column');
    }
    
    /**
     * Return the current options source
     * 
     * @return BL_CustomGrid_Model_Options_Source
     */
    public function getOptionsSource()
    {
        return Mage::registry('blcg_options_source');
    }
    
    /**
     * Return the module name usable for translations
     * 
     * @return string
     */
    protected function _getTranslationModule()
    {
        return 'customgrid';
    }
    
    /**
     * Return the module name to use for translations
     * Wrapper for _getTranslationModule(), with cache
     * 
     * @return string
     */
    public function getTranslationModule()
    {
        if (!$this->hasData('translation_module')) {
            if (!$translationModule = $this->_getTranslationModule()) {
                $translationModule = 'customgrid';
            }
            $this->setData('translation_module', $translationModule);
        }
        return $this->_getData('translation_module');
    }
    
    /**
     * Return the helper usabled for translations, based on the current translation module
     * 
     * @return Mage_Core_Helper_Abstract
     */
    public function getTranslationHelper()
    {
        if (!$this->hasData('translation_helper')) {
            $this->setData('translation_helper', $this->helper($this->getTranslationModule()));
        }
        return $this->_getData('translation_helper');
    }
}
