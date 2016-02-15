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

class BL_CustomGrid_Model_Grid_Type_Sitemap extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    protected function _getSupportedBlockTypes()
    {
        return array('adminhtml/sitemap_grid');
    }
    
    protected function _getBaseEditableFields($blockType)
    {
        /** @var $helper Mage_Adminhtml_Helper_Data */
        $helper = Mage::helper('adminhtml');
        
        $fields = array(
            'sitemap_filename' => array(
                'type'      => 'text',
                'required'  => true,
                'form_note' => $helper->__('example: sitemap.xml'),
            ),
            'sitemap_path' => array(
                'type'      => 'text',
                'required'  => true,
                'form_note' => $helper->__('example: "sitemap/" or "/" for base path (path must be writeable)'),
            ),
        );
        
        if (!Mage::app()->isSingleStoreMode()) {
            $fields['store_id'] = array(
                'type'        => 'select',
                'required'    => true,
                'form_values' => $this->_getEditorHelper()->getStoreValuesForForm(false, false),
            );
        }
        
        return $fields;
    }
    
    protected function _getEntityRowIdentifiersKeys($blockType)
    {
        return array('sitemap_id');
    }
    
    protected function _loadEditedEntity($blockType, BL_CustomGrid_Object $config, array $params, $entityId)
    {
        /** @var $sitemap Mage_Sitemap_Model_Sitemap */
        $sitemap = Mage::getModel('sitemap/sitemap');
        $sitemap->load($entityId);
        return $sitemap;
    }
    
    protected function _getEditRequiredAclPermissions($blockType)
    {
        return 'catalog/sitemap';
    }
    
    /**
     * Return the usable file name for the given edited sitemap
     * 
     * @param BL_CustomGrid_Object $config Edited field config
     * @param Mage_Sitemap_Model_Sitemap $sitemap Edited sitemap
     * @param mixed $value Edited field value
     * @return string
     */
    protected function _getEditedSitemapFileName(BL_CustomGrid_Object $config, $sitemap, $value)
    {
        /** @var $sitemap Mage_Sitemap_Model_Sitemap */
        return ($config->getValueId() != 'sitemap_filename' ? $sitemap->getSitemapFilename() : $value);
    }
    
    /**
     * Return the usable path for the given edited sitemap
     * 
     * @param BL_CustomGrid_Object $config Edited field config
     * @param Mage_Sitemap_Model_Sitemap $sitemap Edited sitemap
     * @param mixed $value Edited field value
     * @return string
     */
    protected function _getEditedSitemapPath(BL_CustomGrid_Object $config, $sitemap, $value)
    {
        /** @var $sitemap Mage_Sitemap_Model_Sitemap */
        return ($config->getValueId() != 'sitemap_path' ? $sitemap->getSitemapPath() : $value);
    }
    
    /**
     * Delete the file for the given edited sitemap
     * 
     * @param Mage_Catalog_Model_Sitemap $sitemap Edited sitemap
     * @return BL_CustomGrid_Model_Grid_Type_Sitemap
     */
    protected function _deleteEditedSitemapFile($sitemap)
    {
        /** @var $sitemap Mage_Sitemap_Model_Sitemap */
        if ($sitemap->getSitemapFilename() && file_exists($sitemap->getPreparedFilename())) {
            unlink($sitemap->getPreparedFilename());
        }
        return $this;
    }
    
    protected function _beforeApplyEditedFieldValue(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        &$value
    ) {
        if ($this->_getBaseHelper()->isMageVersionGreaterThan(1, 5, 0)
            && in_array($config->getValueId(), array('sitemap_filename', 'sitemap_path'))) {
            $fileName = $this->_getEditedSitemapFileName($config, $entity, $value);
            $path = $this->_getEditedSitemapPath($config, $entity, $value);
            
            if (!empty($fileName) && !empty($path)) {
                /** @var $helper Mage_Adminhtml_Helper_Catalog */
                $helper = Mage::helper('adminhtml/catalog');
                $resultPath = rtrim($path, '\\/') . DS . $fileName;
                /** @var $validator Mage_Core_Model_File_Validator_AvailablePath */
                $validator  = Mage::getModel('core/file_validator_availablePath');
                $validator->setPaths($helper->getSitemapValidPaths());
                
                if (!$validator->isValid($resultPath)) {
                    Mage::throwException(implode("\n", $validator->getMessages()));
                }
            }
        }
        
        /** @var $configHelper BL_CustomGrid_Helper_Config_Editor */
        $configHelper = Mage::helper('customgrid/config_editor');
        
        if ($configHelper->getSitemapDeleteFile()) {
            $this->_deleteEditedSitemapFile($entity);
        }
        
        return parent::_beforeApplyEditedFieldValue($blockType, $config, $params, $entity, $value);
    }
}
