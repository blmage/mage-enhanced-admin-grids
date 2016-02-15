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

class BL_CustomGrid_Block_Custom_Column_Config_Form extends BL_CustomGrid_Block_Config_Form_Abstract
{
    public function getFormId()
    {
        return 'blcg_custom_column_config_form';
    }
    
    protected function _getFormCode()
    {
        return 'custom_column_' . $this->getCustomColumn()->getId();
    }
    
    protected function _getFormAction()
    {
        return $this->getUrl('*/*/buildConfig');
    }
    
    protected function _getFormFields()
    {
        $customColumn = $this->getCustomColumn();
        $fields = array();
        
        if ($customColumn->getAllowCustomization()) {
            foreach ($customColumn->getCustomizationParams(true) as $parameter) {
                $fields[] = $parameter;
            }
        }
        
        return $fields;
    }
    
    protected function _getTranslationModule()
    {
        return $this->getCustomColumn()->getModule();
    }
}
