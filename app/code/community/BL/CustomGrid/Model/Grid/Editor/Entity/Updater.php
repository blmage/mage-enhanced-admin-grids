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

class BL_CustomGrid_Model_Grid_Editor_Entity_Updater extends BL_CustomGrid_Model_Grid_Editor_Worker_Abstract
{
    const ACTION_TYPE_CHECK_CONTEXT_VALUE_EDITABILITY          = 'check_context_value_editability';
    const ACTION_TYPE_GET_CONTEXT_USER_EDITED_VALUE            = 'get_context_user_edited_value';
    const ACTION_TYPE_FILTER_USER_EDITED_VALUE                 = 'filter_user_edited_value';
    const ACTION_TYPE_APPLY_USER_EDITED_VALUE_TO_EDITED_ENTITY = 'apply_user_edited_value_to_edited_entity';
    const ACTION_TYPE_SAVE_CONTEXT_EDITED_ENTITY               = 'save_context_edited_entity';
    
    public function getType()
    {
        return BL_Customgrid_Model_Grid_Editor_Abstract::WORKER_TYPE_ENTITY_UPDATER;
    }
    
    /**
     * Default main callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::isContextValueEditable()
     * 
     * @param mixed $previousReturnedValue Value returned by the previous callback
     * @return bool|string
     */
    public function _checkContextValueEditability($previousReturnedValue)
    {
        return (is_string($previousReturnedValue) ? $previousReturnedValue : ($previousReturnedValue !== false));
    }
    
    /**
     * Return whether the value from the given editor context is editable
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return bool|string
     */
    public function isContextValueEditable(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        return $this->_runCallbackedAction(
            self::ACTION_TYPE_CHECK_CONTEXT_VALUE_EDITABILITY,
            array('context' => $context),
            array($this, '_checkContextValueEditability'),
            $context
        );
    }
    
    /**
     * Default main callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::getContextUserEditedValue()
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @param BL_CustomGrid_Object $transport Transport object used to hold the user value
     */
    public function _getContextUserEditedValue(
        BL_CustomGrid_Model_Grid_Editor_Context $context,
        BL_CustomGrid_Object $transport
    ) {
        if (!$transport->hasData('value')) {
            $requestParams = $context->getRequestParams();
            $valueKey = 'values/' . $context->getFormFieldName();
            
            if ($requestParams->hasData($valueKey)) {
                $transport->setData('value', $requestParams->getData($valueKey));
            }
        }
    }
    
    /**
     * Return the user-defined value for the edited value from the given editor context
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return mixed
     */
    public function getContextUserEditedValue(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        $transport = new BL_CustomGrid_Object();
        
        $this->_runCallbackedAction(
            self::ACTION_TYPE_GET_CONTEXT_USER_EDITED_VALUE,
            array('transport' => $transport, 'context' => $context),
            array($this, '_getContextUserEditedValue'),
            $context
        );
        
        if (!$transport->hasData('value')) {
            Mage::throwException('Could not retrieve the user-defined value');
        }
        
        return $transport->getData('value');
    }
    
    /**
     * Default main callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::filterUserEditedValue()
     * 
     * @param BL_CustomGrid_Object $transport Transport object holding the initial and filtered values
     * @param string $filterType Filter type
     */
    public function _filterUserEditedValue(BL_CustomGrid_Object $transport, $filterType)
    {
        if ($filterType == 'date') {
            $transport->setData(
                'filtered_value',
                $this->getEditorHelper()->filterDateValue($transport->getData('filtered_value'))
            );
        }
    }
    
    /**
     * Prepare the given user-defined value so that it is suitable for being applied to the edited entity
     * from the given editor context
     * 
     * @param mixed $userValue User-defined value
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return mixed
     */
    public function filterUserEditedValue($userValue, BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        $valueConfig = $context->getValueConfig();
        $transport   = new BL_CustomGrid_Object(
            array(
                'initial_value'  => $userValue,
                'filtered_value' => $userValue,
            )
        );
        
        if ($valueConfig->getData('updater/must_filter')) {
            if (!$filterType = $valueConfig->getData('updater/filter_type')) {
                if ($context->getValueOrigin() == BL_CustomGrid_Model_Grid_Editor_Abstract::EDITABLE_TYPE_ATTRIBUTE) {
                    $attribute  = $valueConfig->getAttribute();
                    $filterType = $attribute->getFrontend()->getInputType();
                } else {
                    $filterType = $valueConfig->getFormFieldType();
                }
            }
            
            $filterParams = (array) $valueConfig->getData('updater/filter_params');
            
            $this->_runCallbackedAction(
                self::ACTION_TYPE_FILTER_USER_EDITED_VALUE,
                array(
                    'transport'    => $transport,
                    'filterType'   => $filterType,
                    'filterParams' => $filterParams,
                    'context'      => $context,
                ),
                array($this, '_filterUserEditedValue'),
                $context
            );
        }
        
        return $transport->getData('filtered_value');
    }
    
    /**
     * Default main callback for
     * @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::applyUserEditedValueToEditedEntity()
     * 
     * @param mixed $editedEntity Edited entity
     * @param mixed $userValue User-edited value
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @param mixed $previousReturnedValue Value returned by the previous callback
     * @return bool|string
     */
    public function _applyUserEditedValueToEditedEntity(
        $editedEntity,
        $userValue,
        BL_CustomGrid_Model_Grid_Editor_Context $context,
        $previousReturnedValue
    ) {
        $result = $previousReturnedValue;
        
        if ($result !== true) {
            $result = false;
            $valueConfig = $context->getValueConfig();
            
            if (!$valueKey = $valueConfig->getEntityValueKey()) {
                $valueKey = $valueConfig->getFormFieldName();
            }
            
            if ($valueKey) {
                $editedEntity->setData($valueKey, $userValue);
                $result = true;
            }
        }
        
        return $result;
    }
    
    /**
     * Apply the given user-defined value to the given edited entity
     * 
     * @param mixed $editedEntity Edited entity
     * @param mixed $userValue User-edited value
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return bool|string Whether the value was successfully applied to the edited entity
     */
    public function applyUserEditedValueToEditedEntity(
        $editedEntity,
        $userValue,
        BL_CustomGrid_Model_Grid_Editor_Context $context
    ) {
        return $this->_runCallbackedAction(
            self::ACTION_TYPE_APPLY_USER_EDITED_VALUE_TO_EDITED_ENTITY,
            array(
                'editedEntity' => $editedEntity,
                'userValue' => $userValue,
                'context' => $context,
            ),
            array($this, '_applyUserEditedValueToEditedEntity'),
            $context
        );
    }
    
    /**
     * Default main callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::saveContextEditedEntity()
     * 
     * @param mixed $editedEntity Edited entity
     * @param mixed $previousReturnedValue Value returned by the previous callback
     * @return bool|string
     */
    public function _saveContextEditedEntity($editedEntity, $previousReturnedValue)
    {
        $result = $previousReturnedValue;
        
        if ($result !== true) {
            $result = false;
            
            try {
                $editedEntity->save();
                $result = true;
            } catch (Mage_Core_Exception $e) {
                $result = $e->getMessage();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        
        return $result;
    }
    
    /**
     * Save the given edited entity
     * 
     * @param mixed $editedEntity Edited entity
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return bool|string Whether the edited entity was succesfully saved
     */
    public function saveContextEditedEntity($editedEntity, BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        return $this->_runCallbackedAction(
            self::ACTION_TYPE_SAVE_CONTEXT_EDITED_ENTITY,
            array(
                'editedEntity' => $editedEntity,
                'context' => $context,
            ),
            array($this, '_saveContextEditedEntity'),
            $context
        );
    }
    
    /**
     * Update the edited value on the edited entity from the given editor context
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return BL_CustomGrid_Model_Grid_Editor_Entity_Updater
     */
    public function updateContextEditedValue(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        $editedEntity = $context->getEditedEntity();
        $userValue = $this->filterUserEditedValue($this->getContextUserEditedValue($context), $context);
        
        if (($result = $this->applyUserEditedValueToEditedEntity($editedEntity, $userValue, $context)) !== true) {
            $errorMessage = is_string($result)
                ? $result
                : 'Could not apply the user-defined value to the edited entity';
            Mage::throwException($errorMessage);
        }
        if (($result = $this->saveContextEditedEntity($editedEntity, $context)) !== true) {
            $errorMessage = is_string($result)
                ? $result
                : 'Could not save the edited entity';
            Mage::throwException($errorMessage);
        }
        
        return $this;
    }
}
