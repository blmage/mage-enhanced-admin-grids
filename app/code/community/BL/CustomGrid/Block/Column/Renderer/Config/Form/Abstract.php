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

abstract class BL_CustomGrid_Block_Column_Renderer_Config_Form_Abstract extends BL_CustomGrid_Block_Config_Form_Abstract
{
    abstract public function getRenderer();
    abstract public function getRendererType();
    
    protected function _getFormCode()
    {
        return $this->getRendererType() . '_renderer_' . $this->getRenderer()->getCode();
    }
    
    protected function _getFormAction()
    {
        return $this->getUrl('*/*/buildConfig');
    }
    
    protected function _getFormFields()
    {
        $renderer = $this->getRenderer();
        $fields = array();
        
        foreach ($renderer->getParameters() as $parameter) {
            $fields[] = $parameter;
        }
        
        return $fields;
    }
    
    public function getTranslationModule()
    {
        if (!$this->hasData('translation_module')) {
            if (!$module = $this->getRenderer()->getModule()) {
                $module = 'customgrid';
            }
            $this->setData('translation_module', $module);
        }
        return $this->_getData('translation_module');
    }
}
