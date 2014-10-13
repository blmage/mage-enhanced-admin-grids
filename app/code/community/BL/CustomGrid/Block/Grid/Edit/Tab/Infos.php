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
 * @copyright  Copyright (c) 2014 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Grid_Edit_Tab_Infos extends Mage_Adminhtml_Block_Widget_Form implements
    Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function getTabLabel()
    {
        return $this->__('Informations');
    }
    
    public function getTabTitle()
    {
        return $this->__('Informations');
    }
    
    public function canShowTab()
    {
        return true;
    }
    
    public function isHidden()
    {
        return false;
    }
    
    protected function _prepareForm()
    {
        $gridModel = Mage::registry('blcg_grid');
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('blcg_grid_' . $gridModel->getId() . '_infos_');
        
        $fieldset = $form->addFieldset(
            'general',
            array(
                'legend' => $this->__('General'),
                'class'  => 'fielset-wide'
            )
        );
        
        if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_ENABLE_DISABLE)) {
            $fieldset->addField(
                'disabled',
                'select',
                array(
                    'name'     => 'disabled',
                    'label'    => $this->__('Disabled'),
                    'required' => true,
                    'values'   => Mage::getSingleton('customgrid/system_config_source_yesno')->toOptionArray(),
                )
            );
        }
        
        $fieldset->addField(
            'block_type',
            'note',
            array(
                'label' => $this->__('Block Type'),
                'text'  => $gridModel->getBlockType(),
            )
        );
        
        $fieldset->addField(
            'grid_type',
            'note',
            array(
                'label' => $this->__('Grid Type'),
                'text'  => $gridModel->getTypeModelName($this->__('none')),
            )
        );
        
        if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_EDIT_FORCED_TYPE)) {
            $fieldNote = 'Pay attention to the compatibility between the chosen grid type and the current grid, and be '
                . 'careful when changing from one grid type to another (forced or not), if some customizations depend '
                . 'on the current grid type (such as attribute or custom columns)';
            
            $fieldset->addField(
                'forced_type_code',
                'select',
                array(
                    'name'   => 'forced_type_code',
                    'label'  => $this->__('Forced Grid Type'),
                    'note'   => $this->__($fieldNote),
                    'values' => Mage::getSingleton('customgrid/grid_type_config')->getTypesAsOptionHash(true, true),
                )
            );
        }
        
        $fieldset->addField(
            'rewriting_class',
            'note',
            array(
                'label' => $this->__('Rewriting Class'),
                'text'  => $gridModel->getRewritingClassName(),
            )
        );
        
        $fieldset->addField(
            'module_name',
            'note',
            array(
                'label' => $this->__('Module Name'),
                'text'  => $gridModel->getModuleName(),
            )
        );
        
        $fieldset->addField(
            'controller_name',
            'note',
            array(
                'label' => $this->__('Controller Name'),
                'text'  => $gridModel->getControllerName(),
            )
        );
        
        $fieldset->addField(
            'block_id',
            'note',
            array(
                'label' => $this->__('Block ID'),
                'text'  => $gridModel->getBlockId()
                    . ($gridModel->getHasVaryingBlockId() ? ' ' . $this->__('(varying)') : ''),
            )
        );
        
        $form->setValues($gridModel->getData());
        $this->setForm($form);
        
        return parent::_prepareForm();
    }
}
