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

class BL_CustomGrid_Block_Grid_Profile_Form_Edit extends BL_CustomGrid_Block_Grid_Profile_Form_Abstract
{
    protected function _getFormType()
    {
        return 'edit';
    }
    
    protected function _addFieldsToForm(Varien_Data_Form $form)
    {
        $gridProfile = $this->getGridProfile();
        
        $fieldset = $form->addFieldset(
            'values',
            array(
                'legend' => $this->__('Values'),
                'class'  => 'fielset-wide',
            )
        );
        
        if (!$gridProfile->isBase()) {
            $fieldset->addField(
                'name',
                'text',
                array(
                    'name'     => 'name',
                    'label'    => $this->__('Name'),
                    'required' => true,
                    'value'    => $gridProfile->getName(),
                )
            );
        }
        
        $sessionParamsNote = 'Session parameters that will be restored upon returning to this profile, after it had '
            . 'been previously used during the same session.<br /><i>Only applies to the grids having their parameters '
            . 'saved in session</i>';
        
        $sessionParamsField = $fieldset->addField(
            'remembered_session_params',
            'multiselect',
            array(
                'name'   => 'remembered_session_params',
                'label'  => $this->__('Remembered Session Parameters'),
                'values' => $this->_getGridParamsOptionArray(),
                'value'  => $gridProfile->getData('remembered_session_params'),
                'note'   => $this->__($sessionParamsNote),
            )
        );
        
        $this->applyUseConfigCheckboxToElement(
            $sessionParamsField,
            is_null($gridProfile->getData('remembered_session_params')),
            $this->__('Use Grid')
        );
        
        return $this;
    }
}
