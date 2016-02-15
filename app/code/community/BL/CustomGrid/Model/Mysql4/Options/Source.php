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

class BL_CustomGrid_Model_Mysql4_Options_Source extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('customgrid/options_source', 'source_id');
    }
    
    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        if ($object->getId()) {
            $read = $this->_getReadAdapter();
            
            if ($object->getType() == BL_CustomGrid_Model_Options_Source::TYPE_MAGE_MODEL) {
                $select = $read->select()
                    ->from($this->getTable('customgrid/options_source_model'))
                    ->where('source_id = ?', $object->getId());
                
                if (is_array($data = $read->fetchRow($select))) {
                    $object->addData($data);
                }
            } else {
                $select = $read->select()
                    ->from($this->getTable('customgrid/options_source_option'))
                    ->where('source_id = ?', $object->getId());
                
                $options = $read->fetchAll($select);
                $object->setData('options', is_array($options) ? $options : array());
            }
        }
        return parent::_afterLoad($object);
    }
    
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        if ($object->getType() == BL_CustomGrid_Model_Options_Source::TYPE_CUSTOM_LIST) {
            $this->_saveCustomListOptions($object);
        } elseif ($object->getType() == BL_CustomGrid_Model_Options_Source::TYPE_MAGE_MODEL) {
            $this->_saveMageModelConfig($object);
        }
        return parent::_afterSave($object);
    }
    
    /**
     * Save the options values from the custom list of the given source
     * 
     * @param Mage_Core_Model_Abstract $object Options source
     * @return BL_CustomGrid_Model_Mysql4_Options_Source
     */
    protected function _saveCustomListOptions(Mage_Core_Model_Abstract $object)
    {
        $read  = $this->_getReadAdapter();
        $write = $this->_getWriteAdapter();
        $table = $this->getTable('customgrid/options_source_option');
        $options = $object->getOptions();
        
        if (is_array($options)) {
            $select = $read->select()
                ->from($table, 'option_id')
                ->where('source_id = ?', $object->getId());
            
            $existingIds = $read->fetchCol($select);
            $foundIds = array();
            
            foreach ($options as $option) {
                $values  = array(
                    'source_id' => $object->getId(),
                    'value'     => $option['value'],
                    'label'     => $option['label'],
                );
                
                if (!isset($option['delete']) || !$option['delete']) {
                    if (in_array($option['option_id'], $existingIds)) {
                        $write->update(
                            $table,
                            $values,
                            $write->quoteInto('option_id = ?', $option['option_id'])
                        );
                        
                        $foundIds[] = $option['option_id'];
                    } else {
                        $write->insert($table, $values);
                    }
                }
            }
            
            $deletableIds = array_diff($existingIds, $foundIds);
            
            if (!empty($deletableIds)) {
                $write->delete($table, $write->quoteInto('option_id IN (?)', $deletableIds));
            }
        }
        
        return $this;
    }
    
    /**
     * Save the Magento model config from the given source
     * 
     * @param Mage_Core_Model_Abstract $object Options source
     * @return BL_CustomGrid_Model_Mysql4_Options_Source
     */
    protected function _saveMageModelConfig(Mage_Core_Model_Abstract $object)
    {
        $read  = $this->_getReadAdapter();
        $write = $this->_getWriteAdapter();
        $table = $this->getTable('customgrid/options_source_model');
        $deleteWhere = array($write->quoteInto('source_id = ?', $object->getId()));
        
        if ($object->getModelId()) {
            $deleteWhere[] = $write->quoteInto('model_id != ?', $object->getModelId());
        }
        
        $write->delete($table, $deleteWhere);
        
        $values = array(
            'source_id'   => $object->getId(),
            'model_name'  => $object->getModelName(),
            'model_type'  => $object->getModelType(),
            'method'      => $object->getMethod(),
            'return_type' => $object->getReturnType(),
            'value_key'   => $object->getValueKey(),
            'label_key'   => $object->getLabelKey(),
        );
        
        $isModelUpdated = false;
        
        if ($object->getModelId()) {
            $query = $read->select()
                ->from($table)
                ->where('source_id = '.$object->getId().' AND model_id = ?', $object->getModelId());
            
            if ($read->fetchOne($query)) {
                $write->update(
                    $table,
                    $values,
                    $write->quoteInto('model_id = ?', $object->getModelId())
                );
                
                $isModelUpdated = true;
            }
        }
        
        if (!$isModelUpdated) {
            $write->insert($table, $values);
        }
        
        return $this;
    }
}
