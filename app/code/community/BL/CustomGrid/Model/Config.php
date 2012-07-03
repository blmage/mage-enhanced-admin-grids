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

class BL_CustomGrid_Model_Config extends Varien_Object
{
    const CACHE_KEY = 'bl_customgrid_config';
    
    const TYPE_GRID_TYPES = 'grid_types';
    const TYPE_COLUMN_RENDERERS_COLLECTION = 'column_renderers_collection';
    const TYPE_COLUMN_RENDERERS_ATTRIBUTE  = 'column_renderers_attribute';
    
    protected $_xmlConfigs = null;
    
    /**
    * Load whole customgrid configuration, retrieve a sub part
    * 
    * @param string $type Configuration part type
    * @return Varien_Simplexml_Config
    */
    public function getXmlConfig($type)
    {
        if (is_null($this->_xmlConfigs)) {
            $cachedXml = Mage::app()->loadCache(self::CACHE_KEY);
            if ($cachedXml) {
                $xmlConfig = new Varien_Simplexml_Config($cachedXml);
            } else {
                $config = new Varien_Simplexml_Config();
                $config->loadString('<?xml version="1.0"?><customgrid></customgrid>');
                Mage::getConfig()->loadModulesConfiguration('customgrid.xml', $config);
                $xmlConfig = $config;
                if (Mage::app()->useCache('config')) {
                    Mage::app()->saveCache(
                        $config->getXmlString(),
                        self::CACHE_KEY,
                        array(Mage_Core_Model_Config::CACHE_TAG)
                    );
                }
            }
            // Split config in main parts
            $this->_xmlConfigs = array(
                self::TYPE_GRID_TYPES 
                    => new Varien_Simplexml_Config($xmlConfig->getNode('grid_types')),
                self::TYPE_COLUMN_RENDERERS_COLLECTION
                    => new Varien_Simplexml_Config($xmlConfig->getNode('column_renderers/collection')),
                self::TYPE_COLUMN_RENDERERS_ATTRIBUTE 
                    => new Varien_Simplexml_Config($xmlConfig->getNode('column_renderers/attribute')),
            );
        }
        return (isset($this->_xmlConfigs[$type]) ? $this->_xmlConfigs[$type] : null);
    }
}