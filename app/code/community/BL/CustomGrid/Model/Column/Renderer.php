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

abstract class BL_CustomGrid_Model_Column_Renderer
    extends BL_CustomGrid_Model_Config_Abstract
{
    protected $_defaultConfigWindow = array(
        'width'  => 800,
        'height' => 450,
        'title'  => 'Configuration : %s',
    );
    
    public function getElementArrayValues($element, $values, $helper)
    {
        $isCustomizable = (isset($element->parameters) && count($element->parameters));
        
        if ($isCustomizable) {
            if (isset($element->config_window)) {
                $configWindow = $element->config_window->asArray();
                if (isset($configWindow['width'])) {
                    if (($v = intval($configWindow['width'])) > 0) {
                        $configWindow['width'] = $v;
                    } else {
                        unset($configWindow['width']);
                    }
                }
                if (isset($configWindow['height'])) {
                    if (($v = intval($configWindow['height'])) > 0) {
                        $configWindow['height'] = $v;
                    } else {
                        unset($configWindow['height']);
                    }
                }
                $defaultTitle = !isset($configWindow['title']);
                $configWindow += $this->_defaultConfigWindow;
            } else {
                $defaultTitle = true;
                $configWindow = $this->_defaultConfigWindow;
            }
            $titleParam = ($defaultTitle ? $values['name'] : null);
            $configWindow['title'] = $helper->__((string)$configWindow['title'], $titleParam);
        } else {
            $configWindow = null;
        }
        
        return array(
            'is_customizable' => $isCustomizable,
            'config_window'   => $configWindow,
        );
    }
    
    public function getRenderersArray($withEmpty=false)
    {
        $renderers = parent::getElementsArray();
        if ($withEmpty) {
            array_unshift($renderers, array(
                'code' => '',
                'type' => '',
                'name' => '',
                'help' => '',
                'sort_order'  => 0,
                'description' => '',
                'is_customizable' => false,
            ));
        }
        return $renderers;
    }
    
    public function getRendererInstanceByCode($code, $params=null)
    {
        return parent::getElementInstanceByCode($code, $params);
    }
}