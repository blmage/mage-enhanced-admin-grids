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

class BL_CustomGrid_Helper_Editor
    extends Mage_Core_Helper_Abstract
{
    protected function _getRequest()
    {
        return Mage::app()->getRequest();
    }
    
    protected function _cleanWysiwygConfig(&$value, $key, $infos)
    {
        if (is_string($value)) {
            $value = str_replace('/'.$infos['own_route_name'].'/', '/'.$infos['base_route_name'].'/', $value);
        }
    }
    
    public function getWysiwygConfig($config=array())
    {
        $config = Mage::getSingleton('cms/wysiwyg_config')->getConfig($config);
        $data   = $config->getData();
        
        array_walk_recursive($data, array($this, '_cleanWysiwygConfig'), array(
            'own_route_name'  => 'customgrid',
            'base_route_name' => (string)Mage::app()->getConfig()->getNode('admin/routers/adminhtml/args/frontName'),
        ));
        
        $config->setData($data);
        return $config;
    }
    
    public function filterDateValue($value)
    {
        $filterInput = new Zend_Filter_LocalizedToNormalized(array(
            'date_format' => Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT)
        ));
        $filterInternal = new Zend_Filter_NormalizedToLocalized(array(
            'date_format' => Varien_Date::DATE_INTERNAL_FORMAT
        ));
        
        $value = $filterInput->filter($value);
        $value = $filterInternal->filter($value);
        
        return $value;
    }
}