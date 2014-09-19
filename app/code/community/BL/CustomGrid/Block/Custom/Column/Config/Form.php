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

class BL_CustomGrid_Block_Custom_Column_Config_Form
    extends BL_CustomGrid_Block_Config_Form_Abstract
{
    public function getFormId()
    {
        return 'blcg_custom_column_config_form';
    }
    
    protected function _getFormCode()
    {
        return $this->getCustomColumn()->getId();
    }
    
    protected function _getFormAction()
    {
        return $this->getUrl('*/*/buildConfig');
    }
    
    
    protected function _prepareFields(Varien_Data_Form_Element_Fieldset $fieldset)
    {
        $customColumn = $this->getCustomColumn();
        $module = $customColumn->getModule();
        $this->_translationHelper = $this->helper($module ? $module : 'customgrid');
        
        if (!$customColumn->getAllowCustomization()) {
            return $this;
        }
        foreach ($customColumn->getCustomizationParams(true) as $parameter) {
            $this->_addField($fieldset, $parameter);
        }
        
        return $this;
    }
    
    public function getCustomColumn()
    {
        return Mage::registry('blcg_custom_column');
    }
}