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

class BL_CustomGrid_Block_Grid_Form_Grid_Infos extends BL_CustomGrid_Block_Grid_Form_Abstract
{
    public function getFormAction()
    {
        return $this->getUrl('customgrid/grid/saveGridInfos');
    }
    
    public function canDisplayDisabledField()
    {
        return $this->getGridModel()->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_ENABLE_DISABLE);
    }
    
    public function canDisplayForcedTypeCodeField()
    {
        return $this->getGridModel()->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_EDIT_FORCED_TYPE);
    }
    
    public function hasOnlyReadOnlyFields()
    {
        if (!$this->hasData('only_read_only_fields')) {
            $this->setData(
                'only_read_only_fields',
                (!$this->canDisplayDisabledField() && !$this->canDisplayForcedTypeCodeField())
            );
        }
        return $this->_getData('only_read_only_fields');
    }
    
    protected function _addFieldsToForm(Varien_Data_Form $form)
    {
        parent::_addFieldsToForm($form);
        $gridModel = $this->getGridModel();
        
        $fieldset = $form->addFieldset(
            'infos',
            array(
                'legend' => $this->__('Informations'),
                'class'  => 'fielset-wide',
            )
        );
        
        if ($this->canDisplayDisabledField()) {
            $fieldset->addField(
                'disabled',
                'select',
                array(
                    'name'     => 'disabled',
                    'label'    => $this->__('Disabled'),
                    'required' => true,
                    'values'   => Mage::getSingleton('customgrid/system_config_source_yesno')->toOptionArray(),
                    'value'    => ($gridModel->getDisabled() ? '1' : '0'),
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
                'text'  => $gridModel->getBaseTypeModelName($this->__('none')),
            )
        );
        
        if ($this->canDisplayForcedTypeCodeField()) {
            $note = 'Pay attention to the compatibility between the chosen grid type and the current grid, and be '
                . 'careful when changing from one grid type to another (forced or not), if some customizations depend '
                . 'on the current grid type (such as attribute or custom columns)';
            
            $fieldset->addField(
                'forced_type_code',
                'select',
                array(
                    'name'   => 'forced_type_code',
                    'label'  => $this->__('Forced Grid Type'),
                    'note'   => $this->__($note),
                    'values' => Mage::getSingleton('customgrid/grid_type_config')->getTypesAsOptionHash(true, true),
                    'value'  => $gridModel->getForcedTypeCode(),
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
        
        return $this;
    }
}