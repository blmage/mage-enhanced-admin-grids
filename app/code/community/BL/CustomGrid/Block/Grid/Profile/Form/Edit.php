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

class BL_CustomGrid_Block_Grid_Profile_Form_Edit
    extends BL_CustomGrid_Block_Grid_Profile_Form_Abstract
{
    protected function _getFormType()
    {
        return 'edit';
    }
    
    protected function _addFormFields(Varien_Data_Form $form)
    {
        $gridProfile = $this->getGridProfile();
        
        $fieldset = $form->addFieldset('values', array(
            'legend' => $this->__('Profile Values'),
            'class'  => 'fielset-wide',
        ));
        
        $fieldset->addField('name', 'text', array(
            'name'     => 'name',
            'label'    => $this->__('Name'),
            'required' => true,
            'value'    => $gridProfile->getName(),
        ));
        
        return $this;
    }
}