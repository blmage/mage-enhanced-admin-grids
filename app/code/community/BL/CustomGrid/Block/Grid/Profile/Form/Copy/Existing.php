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

class BL_CustomGrid_Block_Grid_Profile_Form_Copy_Existing extends BL_CustomGrid_Block_Grid_Profile_Form_Abstract
{
    protected function _getFormType()
    {
        return 'copy_existing';
    }
    
    protected function _addFieldsToForm(Varien_Data_Form $form)
    {
        $gridModel = $this->getGridModel();
        $profileId = $this->getGridProfile()->getId();
        $profiles  = $gridModel->getProfiles(true, true);
        
        $gridParams  = $this->_getGridParamsOptionArray(false);
        $yesNoValues = $this->_getYesNoOptionArray();
        $profilesValues = array();
        
        foreach ($profiles as $otherProfileId => $otherProfile) {
            if ($otherProfileId !== $profileId) {
                $profilesValues[$otherProfileId] = array(
                    'value' => $otherProfileId,
                    'label' => $otherProfile->getName(),
                );
            }
        }
        
        $fieldset = $form->addFieldset(
            'copy_to',
            array(
                'legend' => $this->__('Copy To'),
                'class'  => 'fielset-wide',
            )
        );
        
        $fieldset->addField(
            'to_profile_id',
            'select',
            array(
                'name'     => 'to_profile_id',
                'label'    => $this->__('Profile'),
                'required' => true,
                'values'   => $profilesValues,
            )
        );
        
        $fieldset = $form->addFieldset(
            'copied_columns',
            array(
                'legend' => $this->__('Copied Values (Columns)'),
                'class'  => 'fielset-wide',
            )
        );
        
        $fieldset->addField(
            'columns',
            'select',
            array(
                'name'     => 'columns',
                'label'    => $this->__('Overwrite Columns'),
                'values'   => $yesNoValues,
            )
        );
        
        $fieldset = $form->addFieldset(
            'copied_default_params',
            array(
                'legend' => $this->__('Copied Values (Default Parameters)'),
                'class'  => 'fielset-wide',
            )
        );
        
        foreach ($gridParams as $gridParam) {
            $fieldset->addField(
                'default_' . $gridParam['value'],
                'select',
                array(
                    'name'   => $gridParam['value'],
                    'label'  => $gridParam['label'],
                    'values' => $yesNoValues,
                )
            );
        }
        
        $this->_addSuffixToFieldsetFieldNames($fieldset, 'default_params');
        return $this;
    }
}
