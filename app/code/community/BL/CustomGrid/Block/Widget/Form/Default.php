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

class BL_CustomGrid_Block_Widget_Form_Default extends BL_CustomGrid_Block_Widget_Form
{
    /**
     * Add base form fields to the given form
     * 
     * @param Varien_Data_Form $form
     * @return BL_CustomGrid_Block_Widget_Form_Default
     */
    protected function _addBaseFieldsToForm(Varien_Data_Form $form)
    {
        return $this;
    }
    
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(
            array(
                'id'     => 'edit_form',
                'action' => $this->getData('action'),
                'method' => 'post'
            )
        );
        
        $this->_addBaseFieldsToForm($form);
        $form->setUseContainer(true);
        $this->setForm($form);
        
        return parent::_prepareForm();
    }
}
