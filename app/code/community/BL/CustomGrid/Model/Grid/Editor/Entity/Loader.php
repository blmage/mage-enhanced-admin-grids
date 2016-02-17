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

class BL_CustomGrid_Model_Grid_Editor_Entity_Loader extends BL_CustomGrid_Model_Grid_Editor_Worker_Abstract
{
    const ACTION_TYPE_GET_ENTITY_ROW_IDENTIFIERS            = 'get_entity_row_identifiers';
    const ACTION_TYPE_GET_CONTEXT_EDITED_ENTITY_IDENTIFIERS = 'get_context_edited_entity_identifiers';
    const ACTION_TYPE_LOAD_EDITED_ENTITY                    = 'load_edited_entity';
    const ACTION_TYPE_RELOAD_CONTEXT_EDITED_ENTITY          = 'reload_edited_entity';
    const ACTION_TYPE_CHECK_EDITED_ENTITY_LOADED_STATE      = 'check_edited_entity_loaded_state';
    const ACTION_TYPE_REGISTER_EDITED_ENTITY                = 'register_edited_entity';
    
    public function getType()
    {
        return BL_CustomGrid_Model_Grid_Editor_Abstract::WORKER_TYPE_ENTITY_LOADER;
    }
    
    /**
     * Return the entity identifiers keys
     * 
     * @param string $blockType Grid block type
     * @return string[]
     */
    protected function _getEntityRowIdentifiersKeys($blockType)
    {
        return is_array($keys = $this->getEditor()->getBaseConfig()->getData('entity_row_identifiers_keys'))
            ? $keys
            : array('id');
    }
    
    /**
     * Extract the value of the entity identifier corresponding to the given key from the given collection row
     * 
     * @param string $blockType Grid block type
     * @param Varien_Object $row Collection row
     * @param string $key Identifier key
     * @return mixed
     */
    protected function _getEntityRowIdentifier($blockType, Varien_Object $row, $key)
    {
        return $row->getData($key);
    }
    
    /**
     * Default main callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Loader::getEntityRowIdentifiers()
     * 
     * @param string $blockType Grid block type
     * @param Varien_Object $row Collection row
     * @param mixed $previousReturnedValue Value returned by the previous callback
     * @return array
     */
    public function _getEntityRowIdentifiers($blockType, Varien_Object $row, $previousReturnedValue)
    {
        $identifiers = (is_array($previousReturnedValue) ? $previousReturnedValue : array());
        
        foreach ($this->_getEntityRowIdentifiersKeys($blockType) as $key) {
            if (!isset($identifiers[$key])) {
                $identifiers[$key] = $this->_getEntityRowIdentifier($blockType, $row, $key);
            }
        }
        
        return $identifiers;
    }
    
    /**
     * Extract all the entity identifiers from the given collection row
     * 
     * @param string $blockType Grid block type
     * @param Varien_Object $row Collection row
     * @return array
     */
    public function getEntityRowIdentifiers($blockType, Varien_Object $row)
    {
         return (array) $this->_runCallbackedAction(
            self::ACTION_TYPE_GET_ENTITY_ROW_IDENTIFIERS,
            array('blockType' => $blockType, 'row' => $row),
            array($this, '_getEntityRowIdentifiers')
         );
    }
    
    /**
     * Default main callback for
     * @see BL_CustomGrid_Model_Grid_Editor_Entity_Loader::getContextEditedEntityIdentifiers()
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @param mixed $previousReturnedValue Value returned by the previous callback
     * @return mixed
     */
    public function _getContextEditedEntityIdentifiers(
        BL_CustomGrid_Model_Grid_Editor_Context $context,
        $previousReturnedValue
    ) {
        $identifiers = (is_array($previousReturnedValue) ? $previousReturnedValue : array());
        $blockType   = $context->getBlockType();
        $requestParams = $context->getRequestParams();
        
        if (is_array($requestIds = $requestParams->getData('ids'))) {
            foreach ($this->_getEntityRowIdentifiersKeys($blockType) as $key) {
                if (isset($requestIds[$key]) && !isset($identifiers[$key])) {
                    $identifiers[$key] = $requestIds[$key];
                }
            }
        }
        
        if (empty($identifiers)) {
            $identifiers = null;
        } elseif (count($identifiers) === 1) {
            $identifiers = end($identifiers);
        }
        
        return $identifiers;
    }
    
    /**
     * Return the identifiers of the edited entity from the given editor context
     * (null if none was found in the request parameters, single value if appropriate, else array)
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return mixed
     */
    public function getContextEditedEntityIdentifiers(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        return $this->_runCallbackedAction(
            self::ACTION_TYPE_GET_CONTEXT_EDITED_ENTITY_IDENTIFIERS,
            array('context' => $context),
            array($this, '_getContextEditedEntityIdentifiers'),
            $context
        );
    }
    
    /**
     * Default main callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Loader::loadEditedEntity()
     * 
     * @param mixed $entityId Entity ID (possibly compound)
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return mixed
     */
    public function _loadEditedEntity($entityId, BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        $entity = null;
        
        if ($modelClassCode = $this->getEditor()->getBaseConfig()->getData('entity_model_class_code')) {
            if ($entity = Mage::getModel($modelClassCode)) {
                $entity->load($entityId);
            }
        }
        
        return $entity;
    }
    
    /**
     * Load and return the edited entity with the given ID
     * 
     * @param mixed $entityId Entity ID (possibly compound)
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return mixed
     */
    public function loadEditedEntity($entityId, BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        return $this->_runCallbackedAction(
            self::ACTION_TYPE_LOAD_EDITED_ENTITY,
            array('entityId' => $entityId, 'context' => $context),
            array($this, '_loadEditedEntity'),
            $context
        );
    }
    
    /**
     * Default main callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Loader::checkEditedEntityLoadedState()
     * 
     * @param mixed $editedEntity Edited entity
     * @param mixed $entityId Entity ID (possibly compound)
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @param mixed $previousReturnedValue Value returned by the previous callback
     * @return bool|string
     */
    public function _checkEditedEntityLoadedState(
        $editedEntity,
        $entityId,
        BL_CustomGrid_Model_Grid_Editor_Context $context,
        $previousReturnedValue
    ) {
        return (($previousReturnedValue === true) || (is_object($editedEntity) && $editedEntity->getId()));
    }
    
    /**
     * Return whether the given edited entity was successfully loaded
     * 
     * @param mixed $editedEntity Edited entity
     * @param mixed $entityId Edited entity ID (possibly compound)
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return bool|string
     */
    public function checkEditedEntityLoadedState(
        $editedEntity,
        $entityId,
        BL_CustomGrid_Model_Grid_Editor_Context $context
    ) {
        return $this->_runCallbackedAction(
            self::ACTION_TYPE_CHECK_EDITED_ENTITY_LOADED_STATE,
            array('editedEntity' => $editedEntity, 'entityId' => $entityId, 'context' => $context),
            array($this, '_checkEditedEntityLoadedState'),
            $context
        );
    }
    
    /**
     * Return the registry keys where the edited entity should be stored
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return string[]
     */
    protected function _getEditedEntityRegistryKeys(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        return array_filter((array) $this->getEditor()->getBaseConfig()->getData('entity_registry_keys'));
    }
    
    /**
     * Default main callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Loader::registerEditedEntity()
     * 
     * @param mixed $editedEntity Edited entity
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     */
    public function _registerEditedEntity($editedEntity, BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        foreach ($this->_getEditedEntityRegistryKeys($context) as $key) {
            Mage::unregister($key);
            Mage::register($key, $editedEntity);
        }
    }
    
    /**
     * Register the given edited entity
     * 
     * @param mixed $editedEntity Edited entity
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return BL_CustomGrid_Model_Grid_Editor_Entity_Loader
     */
    public function registerEditedEntity($editedEntity, BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        $this->_runCallbackedAction(
            self::ACTION_TYPE_REGISTER_EDITED_ENTITY,
            array('editedEntity' => $editedEntity, 'context' => $context),
            array($this, '_registerEditedEntity'),
            $context
        );
        return $this;
    }
    
    /**
     * Default main callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Loader::reloadEditedEntity()
     * 
     * @param mixed $editedEntity Edited entity
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return mixed
     */
    public function _reloadEditedEntity($editedEntity, BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        return $editedEntity->load($editedEntity->getId());
    }
    
    /**
     * Reload and return the given edited entity
     * 
     * @param mixed $editedEntity Edited entity
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return mixed
     */
    public function reloadEditedEntity($editedEntity, BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        return $this->_runCallbackedAction(
            self::ACTION_TYPE_RELOAD_CONTEXT_EDITED_ENTITY,
            array('editedEntity' => $editedEntity, 'context' => $context),
            array($this, '_reloadEditedEntity'),
            $context
         );
    }
    
    /**
     * Load, check, register and return the edited entity from the given editor context
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return mixed
     */
    public function getEditedEntityFromContext(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        $entity = null;
        
        if (is_null($entityId = $this->getContextEditedEntityIdentifiers($context))) {
            Mage::throwException('Could not retrieve the edited entity identifiers from the editor context');
        } elseif (!$entity = $this->loadEditedEntity($entityId, $context)) {
            Mage::throwException('Could not load the edited entity from the editor context'); 
        } elseif (($result = $this->checkEditedEntityLoadedState($entity, $entityId, $context)) !== true) {
            $errorMessage = is_string($result)
                ? $result
                : 'Could not load the edited entity from the editor context';
            Mage::throwException($errorMessage);
        }
        
        $this->registerEditedEntity($entity, $context);
        return $entity;
    }
}
