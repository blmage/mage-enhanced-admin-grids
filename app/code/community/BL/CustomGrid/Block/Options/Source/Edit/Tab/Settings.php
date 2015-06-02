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
 * @copyright  Copyright (c) 2015 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Options_Source_Edit_Tab_Settings extends BL_CustomGrid_Block_Widget_Form implements
    Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function getTabLabel()
    {
        return $this->__('Settings');
    }
    
    public function getTabTitle()
    {
        return $this->__('Settings');
    }
    
    public function canShowTab()
    {
        return true;
    }
    
    public function isHidden()
    {
        return false;
    }
    
    protected function _prepareLayout()
    {
        $continueButton = $this->getLayout()->createBlock('adminhtml/widget_button');
        
        $continueButton->setData(
            array(
                'label'   => $this->__('Continue'),
                'onclick' => "setSettings('" . $this->getContinueUrl() . "', 'options_source_type')",
                'class'   => 'save',
            )
        );
        
        $this->setChild('continue_button', $continueButton);
        return parent::_prepareLayout();
    }
    
    protected function _prepareForm()
    {
        $optionsSource = $this->getOptionsSource();
        
        $form = new Varien_Data_Form();
        $fieldset = $form->addFieldset('settings', array('legend' => $this->__('Settings')));
        
        $fieldset->addField(
            'options_source_type',
            'select',
            array(
                'label'  => $this->__('Type'),
                'title'  => $this->__('Type'),
                'name'   => 'type',
                'value'  => '',
                'values' => $optionsSource->getTypesAsOptionHash(true),
            )
        );
        
        $fieldset->addField(
            'continue_button',
            'note',
            array('text' => $this->getChildHtml('continue_button'))
        );
        
        $this->setForm($form);
        return $this;
    }
    
    /**
     * Return the URL usable to go to the next step of the options source creation
     * 
     * @return string
     */
    public function getContinueUrl()
    {
        return $this->getUrl(
            '*/*/new',
            array(
                'type' => '{{type}}',
                '_current' => true,
            )
        );
    }
}
