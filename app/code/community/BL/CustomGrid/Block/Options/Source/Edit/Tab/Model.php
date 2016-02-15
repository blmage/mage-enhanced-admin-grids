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

class BL_CustomGrid_Block_Options_Source_Edit_Tab_Model extends BL_CustomGrid_Block_Widget_Form implements
    Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function getTabLabel()
    {
        return $this->__('Magento Model');
    }
    
    public function getTabTitle()
    {
        return $this->__('Magento Model');
    }
    
    public function canShowTab()
    {
        return true;
    }
    
    public function isHidden()
    {
        return false;
    }
    
    protected function _prepareDependenceBlock()
    {
        $this->getDependenceBlock()
            ->addFieldMap(
                array(
                    'return_type' => 'return_type',
                    'value_key'   => 'value_key',
                    'label_key'   => 'label_key',
                )
            )
            ->addFieldDependence(
                array(
                    'value_key',
                    'label_key',
                ), 
                'return_type', 
                array(
                    BL_CustomGrid_Model_Options_Source::RETURN_TYPE_OPTION_ARRAY,
                    BL_CustomGrid_Model_Options_Source::RETURN_TYPE_VARIEN_OBJECT_COLLECTION,
                )
            );
        
        return $this;
    }
    
    protected function _prepareForm()
    {
        $optionsSource = $this->getOptionsSource();
        
        $form = new Varien_Data_Form();
        $fieldset = $form->addFieldset('general', array('legend' => $this->__('Model')));
        
        if ($optionsSource->getId()) {
            $fieldset->addField(
                'model_id',
                'hidden',
                array(
                    'name'  => 'model_id',
                    'value' => $optionsSource->getModelId(),
                )
            );
        }
        
        $fieldset->addField(
            'model_name',
            'text',
            array(
                'name'     => 'model_name',
                'label'    => $this->__('Name'),
                'title'    => $this->__('Name'),
                'class'    => 'required-entry',
                'required' => true,
            )
        );
        
        $fieldset->addField(
            'model_type',
            'select',
            array(
                'name'     => 'model_type',
                'label'    => $this->__('Type'),
                'title'    => $this->__('Type'),
                'class'    => 'required-entry',
                'required' => true,
                'values'   => $optionsSource->getModelTypesAsOptionHash(),
            )
        );
        
        $fieldset->addField(
            'method',
            'text',
            array(
                'name'     => 'method',
                'label'    => $this->__('Method'),
                'title'    => $this->__('Method'),
                'class'    => 'required-entry',
                'required' => true,
            )
        );
        
        $fieldset->addField(
            'return_type',
            'select',
            array(
                'name'     => 'return_type',
                'label'    => $this->__('Return Type'),
                'title'    => $this->__('Return Type'),
                'class'    => 'required-entry',
                'required' => true,
                'values'   => $optionsSource->getModelReturnTypesAsOptionHash(),
            )
        );
        
        $fieldset->addField(
            'value_key',
            'text',
            array(
                'name'     => 'value_key',
                'label'    => $this->__('Value Key'),
                'title'    => $this->__('Value Key'),
                'class'    => 'required-entry',
                'required' => true,
            )
        );
        
        $fieldset->addField(
            'label_key',
            'text',
            array(
                'name'     => 'label_key',
                'label'    => $this->__('Label Key'),
                'title'    => $this->__('Label Key'),
                'class'    => 'required-entry',
                'required' => true,
            )
        );
        
        $this->_prepareDependenceBlock();
        $form->setValues($optionsSource->getData());
        $this->setForm($form);
        
        return $this;
    }
}