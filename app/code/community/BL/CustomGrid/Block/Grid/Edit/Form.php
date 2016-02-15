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

class BL_CustomGrid_Block_Grid_Edit_Form extends BL_CustomGrid_Block_Widget_Form_Default
{
    protected function _addBaseFieldsToForm(Varien_Data_Form $form)
    {
        parent::_addBaseFieldsToForm($form);
        
        $gridModel = $this->getGridModel();
        $fieldset  = $form->addFieldset('base_fieldset', array('class' => 'blcg-no-display'));
        
        $fieldset->addField(
            'grid_id',
            'hidden',
            array(
                'name'  => 'grid_id',
                'value' => $gridModel->getId(),
            )
        );
        
        $fieldset->addField(
            'profile_id',
            'hidden',
            array(
                'name'  => 'profile_id',
                'value' => $gridModel->getProfile()->getId(),
            )
        );
        
        return $this;
    }
}
