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

class BL_CustomGrid_Model_Mysql4_Options_Source
    extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('customgrid/options_source', 'source_id');
    }
    
    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        if ($object->getId()) {
            $read = $this->_getReadAdapter();
            
            if ($object->getType() == BL_CustomGrid_Model_Options_Source::SOURCE_TYPE_MAGE_MODEL) {
                // Load corresponding model
                $query = $read->select()
                    ->from($this->getTable('customgrid/options_source_model'))
                    ->where('source_id = ?', $object->getId());
                
                if ($model = $read->fetchRow($query)) {
                    $object->addData($model);
                }
            } else {
                // Load corresponding options
                $query = $read->select()
                    ->from($this->getTable('customgrid/options_source_option'))
                    ->where('source_id = ?', $object->getId());
                
                $options = $read->fetchAll($query);
                $object->setData('options', is_array($options) ? $options : array());
            }
        }
        return parent::_afterLoad($object);
    }
    
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        $read  = $this->_getReadAdapter();
        $write = $this->_getWriteAdapter();
        
        if ($object->getType() == BL_CustomGrid_Model_Options_Source::SOURCE_TYPE_MAGE_MODEL) {
            // Save corresponding model
            $table = $this->getTable('customgrid/options_source_model');
            
            // Delete source model(s)
            $delete = array($write->quoteInto('source_id = ?', $object->getId()));
            if ($object->getModelId()) {
                // But keep updated model if set
                $delete[] = $write->quoteInto('model_id != ?', $object->getModelId());
            }
            $write->delete($table, $delete);
            
            // Insert or update model
            $values = array(
                'source_id'   => $object->getId(),
                'model_name'  => $object->getModelName(),
                'model_type'  => $object->getModelType(),
                'method'      => $object->getMethod(),
                'return_type' => $object->getReturnType(),
                'value_key'   => $object->getValueKey(),
                'label_key'   => $object->getLabelKey(),
            );
            $updated = false;
            
            if ($object->getModelId()) {
                $query = $read->select()
                    ->from($table)
                    ->where('source_id = '.$object->getId().' AND model_id = ?', $object->getModelId());
                
                if ($read->fetchOne($query)) {
                    // Update model if given ID correspond to a model that actually belong to saved source
                    $write->update(
                        $table,
                        $values,
                        $write->quoteInto('model_id = ?', $object->getModelId())
                    );
                    $updated = true;
                }
            }
            
            if (!$updated) {
                // Else (no ID or not found for source), insert it
                $write->insert($table, $values);
            }
        } else {
            // Save corresponding options
            $table   = $this->getTable('customgrid/options_source_option');
            $options = $object->getOptions();
            
            if (is_array($options)) {
                // Get existing options IDs
                $select = $read->select()
                    ->from($table, 'option_id')
                    ->where('source_id = ?', $object->getId());
                
                $existingIds = $read->fetchCol($select);
                $foundIds    = array();
                
                foreach ($options as $option) {
                    $values  = array(
                        'source_id' => $object->getId(),
                        'value'     => $option['value'],
                        'label'     => $option['label'],
                    );            
                    
                    if (($option['option_id'] > 0) 
                        && in_array($option['option_id'], $existingIds)
                        && (!isset($option['delete']) || !$option['delete'])) {
                        // Existing option to update
                        $write->update(
                            $table,
                            $values,
                            $write->quoteInto('option_id = ?', $option['option_id'])
                        );
                        $foundIds[] = $option['option_id'];
                    } elseif (!isset($option['delete']) || !$option['delete']) {
                        // New (or not found for saved source) option to insert
                        $write->insert($table, $values);
                    }
                }
                
                // Remove missing / deleted options
                $delete = array_diff($existingIds, $foundIds);
                if (!empty($delete)) {
                    foreach ($delete as $optionId) {
                        $write->delete($table, $write->quoteInto('option_id = ?', $optionId));
                    }
                }
            }
        }
        
        return parent::_afterSave($object);
    }
}