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

class BL_CustomGrid_Block_Grid_Profile_Form_Assign
    extends BL_CustomGrid_Block_Grid_Profile_Form_Abstract
{
    protected function _getFormType()
    {
        return 'assign';
    }
    
    protected function _addFormFields(Varien_Data_Form $form)
    {
        $gridProfile = $this->getGridProfile();
        
        $fieldset = $form->addFieldset('values', array(
            'legend' => $this->__('Assignation'),
            'class'  => 'fielset-wide',
        ));
        
        $restrictedField = $fieldset->addField('is_restricted', 'select', array(
            'name'     => 'is_restricted',
            'label'    => $this->__('Restricted'),
            'required' => true,
            'values'   => Mage::getSingleton('customgrid/system_config_source_yesno')->toOptionArray(),
        ));
        
        $assignedToField = $fieldset->addField('assigned_to', 'multiselect', array(
            'name'     => 'assigned_to',
            'label'    => $this->__('Assigned To'),
            'required' => true,
            'values'   => Mage::getSingleton('customgrid/system_config_source_admin_role')->toOptionArray(false),
        ));
        
        $dependenceBlock = $this->getLayout()
            ->createBlock('customgrid/widget_form_element_dependence')
            ->addFieldMap($restrictedField->getHtmlId(), 'is_restricted')
            ->addFieldMap($assignedToField->getHtmlId(), 'assigned_to')
            ->addFieldDependence('assigned_to', 'is_restricted', '1');
        
        $form->setValues(array(
            'is_restricted' => ($gridProfile->isRestricted() ? '1' : '0'),
            'assigned_to'   => $gridProfile->getAssignedToRolesIds(),
        ));
        
        $this->setChild('form_after', $dependenceBlock);
        return $this;
    }
}