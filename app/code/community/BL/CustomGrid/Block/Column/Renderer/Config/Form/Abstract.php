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

abstract class BL_CustomGrid_Block_Column_Renderer_Config_Form_Abstract
    extends BL_CustomGrid_Block_Config_Form_Abstract
{
    abstract public function getRenderer();
    
    protected function _getFormCode()
    {
        return $this->getRenderer()->getCode();
    }
    
    protected function _getFormAction()
    {
        return $this->getUrl('*/*/buildConfig');
    }
    
    protected function _prepareFields(Varien_Data_Form_Element_Fieldset $fieldset)
    {
        $renderer = $this->getRenderer();
        
        if ((!$module = $renderer->getModule())
            || (!$this->_translationHelper = $this->helper($module))) {
            $this->_translationHelper = $this->helper('customgrid');
        }
        
        foreach ($renderer->getParameters() as $parameter) {
            $this->_addField($fieldset, $parameter);
        }
        
        return $this;
    }
}