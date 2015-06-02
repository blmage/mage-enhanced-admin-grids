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

class BL_CustomGrid_Model_Config_Manager
{
    const CACHE_KEY = 'bl_customgrid_config';
    
    const TYPE_GRID_TYPES = 'grid_types';
    const TYPE_COLUMN_RENDERERS_COLLECTION = 'column_renderers_collection';
    const TYPE_COLUMN_RENDERERS_ATTRIBUTE  = 'column_renderers_attribute';
    const TYPE_EXCLUDED_GRIDS = 'excluded_grids';
    
    /**
     * XML configurations cache
     * 
     * @var array|null
     */
    protected $_xmlConfigs = null;
    
    /**
     * Load the whole customgrid XML configuration, return the specified sub part
     * 
     * @param string $type Type of the sub part to return
     * @return Varien_Simplexml_Config
     */
    public function getXmlConfig($type)
    {
        if (is_null($this->_xmlConfigs)) {
            $cachedXml = Mage::app()->loadCache(self::CACHE_KEY);
            
            if ($cachedXml) {
                $xmlConfig = new Varien_Simplexml_Config($cachedXml);
            } else {
                $xmlConfig = new Varien_Simplexml_Config();
                $xmlConfig->loadString('<?xml version="1.0"?><customgrid></customgrid>');
                Mage::getConfig()->loadModulesConfiguration('customgrid.xml', $xmlConfig);
                
                if (Mage::app()->useCache('config')) {
                    Mage::app()->saveCache(
                        $xmlConfig->getXmlString(),
                        self::CACHE_KEY,
                        array(Mage_Core_Model_Config::CACHE_TAG)
                    );
                }
            }
            
            // Split config into the main sub parts
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
