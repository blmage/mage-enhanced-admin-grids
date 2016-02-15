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

class BL_CustomGrid_Block_Grid_Profile_Form_Copy_New extends BL_CustomGrid_Block_Grid_Profile_Form_Abstract
{
    protected function _getFormType()
    {
        return 'copy_new';
    }
    
    protected function _addFieldsToForm(Varien_Data_Form $form)
    {
        $gridModel = $this->getGridModel();
        
        $fieldset = $form->addFieldset(
            'values',
            array(
                'legend' => $this->__('New Profile'),
                'class'  => 'fielset-wide',
            )
        );
        
        $fieldset->addField(
            'name',
            'text',
            array(
                'name'     => 'name',
                'label'    => $this->__('Name'),
                'required' => true,
            )
        );
        
        if ($gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_ASSIGN_PROFILES)) {
            $restrictedField = $fieldset->addField(
                'is_restricted',
                'select',
                array(
                    'name'     => 'is_restricted',
                    'label'    => $this->__('Restricted'),
                    'required' => true,
                    'values'   => $this->_getYesNoOptionArray(),
                )
            );
            
            $assignedToField = $fieldset->addField(
                'assigned_to',
                'multiselect',
                array(
                    'name'     => 'assigned_to',
                    'label'    => $this->__('Assigned To'),
                    'required' => true,
                    'values'   => $this->_getAdminRolesOptionArray(false),
                )
            );
            
            $this->getDependenceBlock()
                ->addFieldMap($restrictedField->getHtmlId(), 'is_restricted')
                ->addFieldMap($assignedToField->getHtmlId(), 'assigned_to')
                ->addFieldDependence('assigned_to', 'is_restricted', '1');
        }
        
        return $this;
    }
}
