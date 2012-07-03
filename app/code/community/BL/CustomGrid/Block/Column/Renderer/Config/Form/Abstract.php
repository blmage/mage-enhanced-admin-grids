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
 * @copyright  Copyright (c) 2012 Benoît Leulliette <benoit.leulliette@gmail.com>
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
    
    public function addConfigFields($fieldset)
    {
        $renderer = $this->getRenderer();
        $module   = $renderer->getModule();
        $this->_translationHelper = Mage::helper($module ? $module : 'customgrid');
        
        foreach ($renderer->getParameters() as $parameter) {
            $this->_addConfigField($fieldset, $parameter);
        }
        
        return $this;
    }
}