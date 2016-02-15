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

class BL_CustomGrid_Helper_Editor extends Mage_Core_Helper_Abstract
{
    protected $_adminBaseRouteName = null;
    
    /**
     * Return current request object
     * 
     * @return Mage_Core_Controller_Request_Http
     */
    protected function _getRequest()
    {
        return Mage::app()->getRequest();
    }
    
    /**
     * Return admin base route name
     * 
     * @return string
     */
    protected function _getAdminBaseRouteName()
    {
        if (is_null($this->_adminBaseRouteName)) {
            $this->_adminBaseRouteName = (string) Mage::app()
                ->getConfig()
                ->getNode('admin/routers/adminhtml/args/frontName');
        }
        return $this->_adminBaseRouteName;
    }
    
    /**
     * Clean given wysiwyg config value, by adapting necessary values
     * 
     * @param string $value Config value
     * @param string $key Value key
     * @return BL_CustomGrid_Helper_Editor
     */
    protected function _cleanWysiwygConfig(&$value, $key)
    {
        if (is_string($value)) {
            $value = str_replace('/customgrid/', '/' . $this->_getAdminBaseRouteName() . '/', $value);
        }
        return $this;
    }
    
    /**
     * Return wysiwyg config as Varien_Object
     * Basically calls Mage_Cms_Model_Wysiwyg_Config::getConfig() (check its documentation for more informations),
     * and adapt some necessary values related to the extension
     * 
     * @param array $config Config values
     * @return array
     */
    public function getWysiwygConfig(array $config = array())
    {
        $config = Mage::getSingleton('cms/wysiwyg_config')->getConfig($config);
        $data = $config->getData();
        array_walk_recursive($data, array($this, '_cleanWysiwygConfig'));
        $config->setData($data);
        return $config;
    }
    
    /**
     * Filter given date value
     * 
     * @param string $value Date value
     * @return string
     */
    public function filterDateValue($value)
    {
        $filterInput = new Zend_Filter_LocalizedToNormalized(
            array(
                'date_format' => Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT),
            )
        );
        
        $filterInternal = new Zend_Filter_NormalizedToLocalized(
            array(
                'date_format' => Varien_Date::DATE_INTERNAL_FORMAT,
            )
        );
        
        $value = $filterInput->filter($value);
        $value = $filterInternal->filter($value);
        
        return $value;
    }
    
    /**
     * Return the stores values, ready for being used in forms
     * 
     * @param bool $empty Whether empty value should be included
     * @param bool $all Whether "All Store Views" value should be included
     * @return array
     */
    public function getStoreValuesForForm($empty = false, $all = false)
    {
        /** @var $storeConfig Mage_Adminhtml_Model_System_Store */
        $storeConfig = Mage::getSingleton('adminhtml/system_store');
        return $storeConfig->getStoreValuesForForm($empty, $all);
    }
}
