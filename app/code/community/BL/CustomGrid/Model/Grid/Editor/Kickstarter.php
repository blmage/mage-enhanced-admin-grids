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

class BL_CustomGrid_Model_Grid_Editor_Kickstarter extends BL_CustomGrid_Model_Grid_Editor_Worker_Abstract
{
    const ACTION_TYPE_PREPARE_EDITOR_CONTEXT_FROM_REQUEST = 'prepare_editor_context_from_request';
    const ACTION_TYPE_EXTRACT_REQUEST_EDIT_PARAMS         = 'extract_request_edit_params';
    const ACTION_TYPE_CHECK_EDITOR_CONTEXT_VALIDITY       = 'check_editor_context_validity';
    
    public function getType()
    {
        return BL_CustomGrid_Model_Grid_Editor_Abstract::WORKER_TYPE_KICKSTARTER;
    }
    
    /**
     * Default main callback for @see BL_CustomGrid_Model_Grid_Editor_Kickstarter::prepareEditorContextFromRequest()
     * 
     * @param Mage_Core_Controller_Request_Http $request Editor request
     * @param BL_CustomGrid_Model_Grid $gridModel Current grid model
     * @param mixed $previousReturnedValue Value returned by the previous callback
     * @return BL_CustomGrid_Model_Grid_Editor_Context
     */
    public function _prepareEditorContextFromRequest(
        Mage_Core_Controller_Request_Http $request,
        BL_CustomGrid_Model_Grid $gridModel,
        $previousReturnedValue
    ) {
        /** @var Mage_Core_Helper_Data $helper */
        $helper = Mage::helper('core');
        
        /** @var BL_CustomGrid_Model_Grid_Editor_Context $context */
        $context = ($previousReturnedValue instanceof BL_CustomGrid_Model_Grid_Editor_Context)
            ? $previousReturnedValue
            : new BL_CustomGrid_Model_Grid_Editor_Context();
        
        if (($blockType = $request->getParam('block_type', null))
            && ($blockType = $helper->urlDecode($blockType))
            && ($valueId = $request->getParam('value_id', null))
            && ($valueOrigin = $request->getParam('value_origin', null))) {
            $context->addData(
                array(
                    'grid_model'   => $gridModel,
                    'block_type'   => $blockType,
                    'value_id'     => $valueId,
                    'value_origin' => $valueOrigin,
                )
            );
        } else {
            Mage::throwException('Could not prepare an editor context from the given request');
        }
        
        return $context;
    }
    
    /**
     * Prepare and return an editor context from the given request
     * (important note: at that point, the returned context only contains a small subset of its possible values)
     * 
     * @param Mage_Core_Controller_Request_Http $request Editor request
     * @param BL_CustomGrid_Model_Grid $gridModel Current grid model
     * @return BL_CustomGrid_Model_Grid_Editor_Context
     */
    public function prepareEditorContextFromRequest(
        Mage_Core_Controller_Request_Http $request,
        BL_CustomGrid_Model_Grid $gridModel
    ) {
        return $this->_runCallbackedAction(
            self::ACTION_TYPE_PREPARE_EDITOR_CONTEXT_FROM_REQUEST,
            array('request' => $request, 'gridModel' => $gridModel),
            array($this, '_prepareEditorContextFromRequest')
        );
    }
    
    /**
     * Default main callback for @see BL_CustomGrid_Model_Grid_Editor_Kickstarter::extractRequestEditParams()
     * 
     * @param Mage_Core_Controller_Request_Http $request Editor request
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @param mixed $previousReturnedValue Value returned by the previous callback
     * @return array
     */
    public function _extractRequestEditParams(
        Mage_Core_Controller_Request_Http $request,
        BL_CustomGrid_Model_Grid_Editor_Context $context,
        $previousReturnedValue
    ) {
        $context->setData('origin_grid_type_code', $request->getParam('grid_type', null));
        
        $valueConfig   = $context->getValueConfig();
        $idsKey        = $valueConfig->getData('request/ids_key');
        $valuesKey     = $valueConfig->getData('request/values_key');
        $additionalKey = $valueConfig->getData('request/additional_key');
        $usedKeys      = array_flip(array($idsKey, $valuesKey, $additionalKey));
        
        $params = array_merge_recursive(
            (is_array($previousReturnedValue) ? $previousReturnedValue : array()),
            array(
                'ids'        => (array) $request->getParam($idsKey, array()),
                'values'     => (array) $request->getParam($valuesKey, array()),
                'additional' => (array) $request->getParam($additionalKey, array()),
                'global'     => array_diff_key($request->getParams(), $usedKeys),
            )
        );
        
        foreach ($params as $key => $param) {
            if (!is_array($param)) {
                $params[$key] = array();
            }
        }
        
        return $params;
    }
    
    /**
     * Extract and return the edit parameters contained in the given request
     * 
     * @param Mage_Core_Controller_Request_Http $request Editor request
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return array
     */
    public function extractRequestEditParams(
        Mage_Core_Controller_Request_Http $request,
        BL_CustomGrid_Model_Grid_Editor_Context $context
    ) {
        return $this->_runCallbackedAction(
            self::ACTION_TYPE_EXTRACT_REQUEST_EDIT_PARAMS,
            array('request' => $request, 'context' => $context),
            array($this, '_extractRequestEditParams')
        );
    }
    
    /**
     * Default main callback for @see BL_CustomGrid_Model_Grid_Editor_Kickstarter::checkEditorContextValidity()
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @param mixed $previousReturnedValue Value returned by the previous callback
     * @return bool|string
     */
    public function _checkEditorContextValidity(
        BL_CustomGrid_Model_Grid_Editor_Context $context,
        $previousReturnedValue
    ) {
        $result = $previousReturnedValue;
        
        if (($result !== false) && !is_string($result)) {
            $result = true;
        }
        
        return $result;
    }
    
    /**
     * Return whether the given editor context is still valid, in regard to the original user intent
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return bool|string
     */
    public function checkEditorContextValidity(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        return $this->_runCallbackedAction(
            self::ACTION_TYPE_CHECK_EDITOR_CONTEXT_VALIDITY,
            array('context' => $context),
            array($this, '_checkEditorContextValidity'),
            $context
        );
    }
    
    /**
     * Return a fully initialized editor context from the given editor request
     * 
     * @param Mage_Core_Controller_Request_Http $request Editor request
     * @param BL_CustomGrid_Model_Grid $gridModel Current grid model
     * @return BL_CustomGrid_Model_Grid_Editor_Context
     */
    public function getEditorContextFromRequest(
        Mage_Core_Controller_Request_Http $request,
        BL_CustomGrid_Model_Grid $gridModel
    ) {
        $context = $this->prepareEditorContextFromRequest($request, $gridModel);
        $valueConfig = null;
        
        if (!$context instanceof BL_CustomGrid_Model_Grid_Editor_Context) {
            Mage::throwException('Invalid editor context');
        }
        if ($context->getValueOrigin() == BL_CustomGrid_Model_Grid_Editor_Abstract::EDITABLE_TYPE_CUSTOM_COLUMN) {
            $gridColumns = $context->getGridModel()->getColumns(true, true);
            $columnBlockId = $context->getValueId();
            
            if (isset($gridColumns[$columnBlockId]) && $gridColumns[$columnBlockId]->isCustom()) {
                /** @var BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig */
                $valueConfig = $gridColumns[$columnBlockId]->getEditorConfig();
                $context->setData('grid_column', $gridColumns[$columnBlockId]);
                $context->setData('value_id', $valueConfig->getData('global/base_value_id'));
                $context->setData('value_origin', $valueConfig->getData('global/base_value_origin'));
            }
        } else {
            $valueConfig = $this->getEditor()->getContextEditableValueConfig($context);
        }
        if (!$valueConfig) {
            Mage::throwException($this->getBaseHelper()->__('This value is not editable'));
        }
        
        $context->setData('value_config', $valueConfig);
        
        if (!is_array($requestParams = $this->extractRequestEditParams($request, $context))) {
            Mage::throwException('Could not extract the edit parameters from the current request');
        }
        
        $context->setData('request_params', new BL_CustomGrid_Object($requestParams));
        
        if (($result = $this->checkEditorContextValidity($context)) !== true) {
            $errorMessage = is_string($result)
                ? $result
                : $this->getBaseHelper()
                    ->__('The context has significantly changed. Refresh the grid before retrying to edit the value.');
            Mage::throwException($errorMessage);
        }
        
        $context->setData(
            'edited_entity',
            $this->getEditor()
                ->getEntityLoader()
                ->getEditedEntityFromContext($context)
        );
        
        return $context;
    }
}
