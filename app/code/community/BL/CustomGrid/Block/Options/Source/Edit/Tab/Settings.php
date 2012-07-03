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

class BL_CustomGrid_Block_Options_Source_Edit_Tab_Settings
    extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareLayout()
    {
        $this->setChild('continue_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                        'label'   => $this->__('Continue'),
                        'onclick' => "setSettings('".$this->getContinueUrl()."', 'options_source_type')",
                        'class'   => 'save',
                    ))
                );
        return parent::_prepareLayout();
    }
    
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $fieldset = $form->addFieldset('settings', array(
            'legend' => $this->__('Options Source Creation Settings')
        ));
        
        $fieldset->addField('options_source_type', 'select', array(
            'label'  => $this->__('Type'),
            'title'  => $this->__('Type'),
            'name'   => 'type',
            'value'  => '',
            'values' => Mage::getModel('customgrid/options_source')->getTypesAsOptionHash(true)
        ));
        
        $fieldset->addField('continue_button', 'note', array(
            'text' => $this->getChildHtml('continue_button'),
        ));
        
        $this->setForm($form);
    }
    
    public function getContinueUrl()
    {
        return $this->getUrl('*/*/new', array(
            '_current' => true,
            'type'     => '{{type}}'
        ));
    }
}