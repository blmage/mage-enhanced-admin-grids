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

class BL_CustomGrid_Block_Options_Source_Edit_Tab_General
    extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $source = Mage::registry('current_options_source');
        
        $form = new Varien_Data_Form();
        $fieldset = $form->addFieldset('general', array('legend' => $this->__('General')));
        
        if (!$source->getId()) {
            $fieldset->addField('type', 'hidden', array(
                'name'  => 'type',
                'value' => $this->getRequest()->getParam('type', null),
            ));
        }
        
        $fieldset->addField('name', 'text', array(
            'name'     => 'name',
            'label'    => $this->__('Name'),
            'title'    => $this->__('Name'),
            'class'    => 'required-entry',
            'required' => true,
        ));
        
        $fieldset->addField('description', 'textarea', array(
            'name'  => 'description',
            'label' => $this->__('Description'),
            'title' => $this->__('Description'),
        ));
        
        $form->setValues($source->getData());
        $this->setForm($form);
    }
}