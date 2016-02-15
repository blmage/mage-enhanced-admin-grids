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

class BL_CustomGrid_Block_Options_Source_Edit_Tab_Custom_List extends BL_CustomGrid_Block_Widget_Form implements
    Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * Base form element
     * 
     * @var Varien_Data_Form_Element_Abstract
     */
    protected $_element;
    
    public function __construct()
    {
        $this->setTemplate('bl/customgrid/options/source/edit/custom/list.phtml');
    }
    
    protected function _prepareLayout()
    {
        /** @var $addButton Mage_Adminhtml_Block_Widget_Button */
        $addButton = $this->getLayout()->createBlock('adminhtml/widget_button');
        
        $addButton->setData(
            array(
                'name'    => 'add_custom_option_item_button',
                'label'   => $this->helper('catalog')->__('Add Option'),
                'onclick' => 'return blcgOptionsSourceControl.addItem()',
                'class'   => 'add',
            )
        );
        
        $this->setChild('add_button', $addButton);
        return parent::_prepareLayout();
    }
    
    /**
     * Callback for values sorting
     * 
     * @param array $valueA One value
     * @param array $valueB Another value
     */
    protected function _sortValues($valueA, $valueB)
    {
        $result = strcmp($valueA['value'], $valueB['value']);
        return ($result === 0 ? strcasecmp($valueA['label'], $valueB['label']) : $result);
    }
    
    /**
     * Return the options source values as an option array
     * 
     * @return array
     */
    public function getValues()
    {
        $values = $this->_element->getValue();
        
        if (is_array($values)) {
            usort($values, array($this, '_sortValues'));
        } else {
            $values = array();
        }
        
        return $values;
    }
    
    /**
     * Return the HTML content of the "Add option" button
     * 
     * @return string
     */
    public function getAddButtonHtml()
    {
        return $this->getChildHtml('add_button');
    }
    
    /**
     * Set the base form element
     * 
     * @param Varien_Data_Form_Element_Abstract $element Base form element
     * @return BL_CustomGrid_Block_Options_Source_Edit_Tab_Custom_List
     */
    public function setElement(Varien_Data_Form_Element_Abstract $element)
    {
        $this->_element = $element;
        return $this;
    }
    
    /**
     * Return the base form element
     * 
     * @return Varien_Data_Form_Element_Abstract
     */
    public function getElement()
    {
        return $this->_element;
    }
    
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        return $this->toHtml();
    }
}
