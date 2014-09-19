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

class BL_CustomGrid_Model_Grid_Type_Sitemap
    extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    protected function _getSupportedBlockTypes()
    {
        return 'adminhtml/sitemap_grid';
    }
    
    protected function _getBaseEditableFields($blockType)
    {
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
                'form_values' => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(),
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
        return Mage::getModel('sitemap/sitemap')->load($entityId);
    }
    
    protected function _getEditRequiredAclPermissions($blockType)
    {
        return 'catalog/sitemap';
    }
    
    protected function _beforeApplyEditedFieldValue($blockType, BL_CustomGrid_Object $config, array $params, $entity,
        &$value)
    {
        if ($this->_getHelper()->isMageVersionGreaterThan(1, 5, 0)
            && in_array($config->getId(), array('sitemap_filename', 'sitemap_path'))) {
            $fileName = ($config->getId() != 'sitemap_filename' ? $entity->getSitemapFilename() : $value);
            $path = ($config->getId() != 'sitemap_path' ? $entity->getSitemapPath() : $value);
            
            if (!empty($fileName) && !empty($path)) {
                $helper = Mage::helper('adminhtml/catalog');
                $resultPath = rtrim($path, '\\/') . DS . $fileName;
                $validator  = Mage::getModel('core/file_validator_availablePath');
                $validator->setPaths($helper->getSitemapValidPaths());
                
                if (!$validator->isValid($resultPath)) {
                    Mage::throwException(implode("\n", $validator->getMessages()));
                }
            }
        }
        
        if (Mage::helper('customgrid/config_editor')->getSitemapDeleteFile()
            && $entity->getSitemapFilename()
            && file_exists($entity->getPreparedFilename())) {
            unlink($entity->getPreparedFilename());
        }
        
        return parent::_beforeApplyEditedFieldValue($blockType, $config, $params, $entity, $value);
    }
}