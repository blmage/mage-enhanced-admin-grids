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

abstract class BL_CustomGrid_Model_Column_Renderer_Config_Abstract extends BL_CustomGrid_Model_Config_Abstract
{
    /**
     * Default configuration window values
     * 
     * @var array
     */
    static protected $_defaultConfigWindow = array(
        'width'  => 800,
        'height' => 450,
        'title'  => 'Configuration : %s',
    );
    
    public function getAcceptParameters()
    {
        return true;
    }
    
    /**
     * Load and parse the config window values from the given XML element into the given element model
     * 
     * @param BL_CustomGrid_Object $model Element model
     * @param Varien_Simplexml_Element $xmlElement Corresponding XML element
     * @return BL_CustomGrid_Model_Column_Renderer_Config_Abstract
     */
     protected function _loadElementModelConfigWindow(BL_CustomGrid_Object $model, Varien_Simplexml_Element $xmlElement)
     {
        $configWindow = self::$_defaultConfigWindow;
        $useDefaultTitle = true;
        
        if ($windowXmlElement = $xmlElement->descend('config_window')) {
            $windowValues = $windowXmlElement->asCanonicalArray();
            
            if (isset($windowValues['width'])) {
                if (($value = (int) $windowValues['width']) > 0) {
                    $configWindow['width'] = $value;
                }
            }
            if (isset($windowValues['height'])) {
                if (($value = (int) $windowValues['height']) > 0) {
                    $configWindow['height'] = $value;
                }
            }
            if (isset($windowValues['title'])) {
                $useDefaultTitle = false;
                $configWindow['title'] = $windowValues['title'];
            }
            
            $configWindow += $windowValues;
        }
        if ($useDefaultTitle) {
            $configWindow['title'] = $model->getHelper()->__($configWindow['title'], $model->getName());
        }
        
        $model->setData('config_window', $configWindow);
        return $this;
    }
    
    protected function _prepareElementModel(BL_CustomGrid_Object $model, Varien_Simplexml_Element $xmlElement)
    {
        if ($model->isCustomizable()) {
            $this->_loadElementModelConfigWindow($model, $xmlElement);
        }
        return $this;
    }
    
    /**
     * Return the renderer model corresponding to the given code
     * 
     * @param string $code Column renderer code
     * @return BL_CustomGrid_Model_Column_Renderer_Abstract|null
     */
    public function getRendererModelByCode($code)
    {
        return parent::getElementModelByCode($code);
    }
    
    /**
     * Return all the available renderers models
     * 
     * @param bool $sorted Whether the rendererers models should be sorted
     * @return BL_CustomGrid_Model_Column_Renderer_Abstract[]
     */
    public function getRenderersModels($sorted = false)
    {
        return parent::getElementsModels($sorted);
    }
}
