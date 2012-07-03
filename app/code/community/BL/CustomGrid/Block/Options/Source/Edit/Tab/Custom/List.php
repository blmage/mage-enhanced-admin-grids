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
 * @copyright  Copyright (c) 2012 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Options_Source_Edit_Tab_Custom_List
    extends Mage_Adminhtml_Block_Widget_Form
    implements Varien_Data_Form_Element_Renderer_Interface
{
    protected $_element;
    
    public function __construct()
    {
        $this->setTemplate('bl/customgrid/options/source/edit/custom/list.phtml');
    }
    
    public function getOptionsSource()
    {
        return Mage::registry('options_source');
    }
    
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        return $this->toHtml();
    }
    
    public function setElement(Varien_Data_Form_Element_Abstract $element)
    {
        $this->_element = $element;
        return $this;
    }
    
    public function getElement()
    {
        return $this->_element;
    }
    
    public function getValues()
    {
        $values = array();
        $data   = $this->getElement()->getValue();
        
        if (is_array($data)) {
            usort($data, array($this, '_sortValues'));
            $values = $data;
        }
        
        return $values;
    }
    
    protected function _sortValues($a, $b)
    {
        $result = strcmp($a['value'], $b['value']);
        return ($result === 0 ? strcasecmp($a['label'], $b['label']) : $result);
    }
    
    protected function _prepareLayout()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'label'   => Mage::helper('catalog')->__('Add Option'),
                'onclick' => 'return customOptionsSourceControl.addItem()',
                'class'   => 'add',
            ));
        $button->setName('add_custom_option_item_button');
        
        $this->setChild('add_button', $button);
        return parent::_prepareLayout();
    }
    
    public function getAddButtonHtml()
    {
        return $this->getChildHtml('add_button');
    }
}