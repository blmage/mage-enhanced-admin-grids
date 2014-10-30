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

abstract class BL_CustomGrid_Model_Column_Renderer_Config_Abstract extends BL_CustomGrid_Model_Config_Abstract
{
    static protected $_defaultConfigWindow = array(
        'width'  => 800,
        'height' => 450,
        'title'  => 'Configuration : %s',
    );
    
    public function getAcceptParameters()
    {
        return true;
    }
    
    public function getRendererInstanceByCode($code, $parameters = null)
    {
        return parent::getElementInstanceByCode($code, $parameters);
    }
    
    protected function _getConfigWindowValues(
        Varien_Simplexml_Element $xmlElement,
        array $baseValues,
        Mage_Core_Helper_Abstract $helper
    ) {
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
            if (isset($baseValues['name'])) {
                $configWindow['title'] = $helper->__($configWindow['title'], $baseValues['name']);
            } else {
                $configWindow['title'] = '';
            }
        }
        
        return $configWindow;
    }
    
    public function getElementsArrayAdditionalSubValues(
        Varien_Simplexml_Element $xmlElement,
        array $baseValues,
        Mage_Core_Helper_Abstract $helper
    ) {
        $configWindow = null;
        $isCustomizable = (bool) $xmlElement->descend('parameters');
        
        if ($isCustomizable) {
            $configWindow = $this->_getConfigWindowValues($xmlElement, $baseValues, $helper);
        }
        
        return array(
            'config_window'   => $configWindow,
            'is_customizable' => $isCustomizable,
        );
    }
    
    public function getRenderersArray()
    {
        return $this->getElementsArray();
    }
    
    public function getRenderersInstances()
    {
        $models = array();
        
        foreach ($this->getElementsCodes() as $code) {
            if ($model = $this->getElementInstanceByCode($code)) {
                $models[$code] = $model;
            }
        }
        
        return $models;
    }
}
