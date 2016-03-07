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

class BL_CustomGrid_Block_Grid_Form_Columns_List extends BL_CustomGrid_Block_Widget_Grid_Config_Columns_List
{
    /**
     * Return the current grid model
     *
     * @return BL_CustomGrid_Model_Grid
     */
    public function getGridModel()
    {
        return $this->getDataSetDefault('grid_model', Mage::registry('blcg_grid'));
    }
    
    public function isStandAlone()
    {
        return false;
    }
    
    /**
     * When used as a stand-alone form, tell that the columns list should be saved using Ajax
     * 
     * @return bool
     */
    public function getUseAjaxSubmit()
    {
        return true;
    }
    
    /**
     * When used as a stand-alone form, tell that the corresponding grid should be reloaded after the list is saved
     *
     * @return bool
     */
    public function getReloadGridAfterSuccess()
    {
        return true;
    }
    
    /**
     * Prepare the given form container so that it is suited for displaying the columns list form
     * 
     * @param BL_CustomGrid_Block_Grid_Form_Container $formContainer Form container
     * @return BL_CustomGrid_Block_Grid_Form_Columns_List
     */
    public function prepareFormContainer(BL_CustomGrid_Block_Grid_Form_Container $formContainer)
    {
        $formContainer->setIsFrameContainedForm(true);
        
        if ($this->canHaveAttributeColumns()) {
            $formContainer->addButton(
                'blcg_attribute_columns',
                array(
                    'label'   => $this->__('Add Attribute Column'),
                    'onclick' => $this->getConfigJsObjectName() . '.addColumn();',
                    'class'   => 'add',
                ),
                0,
                -1000
            );
        }
        
        return $this;
    }
}
