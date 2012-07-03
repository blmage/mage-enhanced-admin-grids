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

class BL_CustomGrid_Model_Grid_Type_Sitemap
    extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    public function isAppliableToGrid($type, $rewritingClassName)
    {
        return ($type == 'adminhtml/sitemap_grid');
    }
    
    public function checkUserEditPermissions($type, $model, $block=null, $params=array())
    {
        if (parent::checkUserEditPermissions($type, $model, $block, $params)) {
            return Mage::getSingleton('admin/session')->isAllowed('catalog/sitemap');
        }
        return false;
    }
    
    protected function _getBaseEditableFields($type)
    {
        $helper = Mage::helper('sitemap');
        
        $fields = array(
            'sitemap_filename' => array(
                'type'      => 'text',
                'required'  => true,
                'form_note' => Mage::helper('adminhtml')->__('example: sitemap.xml'),
            ),
            'sitemap_path' => array(
                'type'      => 'text',
                'required'  => true,
                'form_note' => Mage::helper('adminhtml')->__('example: "sitemap/" or "/" for base path (path must be writeable)'),
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
    
    protected function _getEntityRowIdentifiersKeys($type)
    {
        return array('sitemap_id');
    }
    
    protected function _loadEditedEntity($type, $config, $params)
    {
        if (isset($params['ids']['sitemap_id'])) {
            return Mage::getModel('sitemap/sitemap')->load($params['ids']['sitemap_id']);
        }
        return null;
    }
    
    protected function _beforeApplyEditedFieldValue($type, $config, $params, $entity, &$value)
    {
        if (Mage::helper('customgrid')->isMageVersionGreaterThan(1, 5, 0)
            && in_array($config['id'], array('sitemap_filename', 'sitemap_path'))) {
            $fileName = ($config['id'] == 'sitemap_filename' ? $entity->getSitemapFilename() : $value);
            $path     = ($config['id'] == 'sitemap_path' ? $entity->getSitemapPath() : $value);
            
            if (!empty($fileName) && !empty($path)) {
                $resultPath = rtrim($path, '\\/') . DS . $fileName;
                $helper     = Mage::helper('adminhtml/catalog');
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
        
        return parent::_beforeApplyEditedFieldValue($type, $config, $params, $entity, $value);
    }
}