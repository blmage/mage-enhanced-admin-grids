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

class BL_CustomGrid_Model_Custom_Column_Applier extends BL_CustomGrid_Model_Custom_Column_Worker_Abstract
{
    // Possible behaviours to use when grid block / collection can not be verified
    const UNVERIFIED_BEHAVIOUR_NONE    = 'none';
    const UNVERIFIED_BEHAVIOUR_WARNING = 'warning';
    const UNVERIFIED_BEHAVIOUR_STOP    = 'stop';
    
    // Key where to store the verification messages flags in session
    const VERIFICATION_MESSAGES_FLAGS_SESSION_KEY = 'blcg_cc_vm_flags';
    
    public function getType()
    {
        return BL_CustomGrid_Model_Custom_Column_Abstract::WORKER_TYPE_APPLIER;
    }
    
    /**
     * Return the base helper
     * 
     * @return BL_CustomGrid_Helper_Data
     */
    public function getBaseHelper()
    {
        return Mage::helper('customgrid');
    }
    
    /**
     * Return whether a message of the given type was not already displayed during the current session,
     * for the given element type and block type, and set the flag if not
     * 
     * @param string $messageType Message type ("warning" or "error")
     * @param string $elementType Verified element type ("block" or "collection")
     * @param string $blockType Block type
     * @return bool
     */
    protected function _canDisplayVerificationMessage($messageType, $elementType, $blockType)
    {
        /** @var $session Mage_Admin_Model_Session */
        $session = Mage::getSingleton('admin/session');
        
        if (!is_array($flags = $session->getData(self::VERIFICATION_MESSAGES_FLAGS_SESSION_KEY))) {
            $flags = array();
        }
        if (!isset($flags[$elementType])) {
            $flags[$elementType] = array();
        }
        if (!isset($flags[$elementType][$blockType])) {
            $flags[$elementType][$blockType] = array();
        }
        if ($flag = !isset($flags[$elementType][$blockType][$messageType])) {
            $flags[$elementType][$blockType][$messageType] = true;
            $session->setData(self::VERIFICATION_MESSAGES_FLAGS_SESSION_KEY, $flags);
        }
        
        return $flag;
    }
    
    /**
     * Reset the verification messages flags for the given element type and block type
     * 
     * @param string $elementType Verified element type ("block" or "collection")
     * @param string $blockType Block type
     * @return BL_CustomGrid_Model_Custom_Column_Applier
     */
    protected function _resetVerificationMessagesFlags($elementType, $blockType)
    {
        /** @var $session Mage_Admin_Model_Session */
        $session = Mage::getSingleton('admin/session');
        
        if (is_array($flags = $session->getData(self::VERIFICATION_MESSAGES_FLAGS_SESSION_KEY))) {
            if (isset($flags[$elementType])) {
                $flags[$elementType][$blockType] = array();
            }
            $session->setData(self::VERIFICATION_MESSAGES_FLAGS_SESSION_KEY, $flags);
        }
        
        return $this;
    }
    
    /**
     * Return the behaviour usable if an element from the given type can not be verified
     * 
     * @param string $elementType Verified element type ("block" or "collection")
     * @return string
     */
    protected function _getUnverifiedElementBehaviour($elementType)
    {
        /** @var $helper BL_CustomGrid_Helper_Config */
        $helper = Mage::helper('customgrid/config');
        $behaviour = null;
        
        if ($elementType == 'block') {
            $behaviour = $helper->getCustomColumnsUnverifiedBlockBehaviour();
        } elseif ($elementType == 'collection') {
            $behaviour = $helper->getCustomColumnsUnverifiedCollectionBehaviour();
        }
        
        return $behaviour;
    }
    
    /**
     * Return the verification result for the grid element of the given type
     * 
     * @param string $elementType Verified element type ("block" or "collection")
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @return bool
     */
    protected function _getGridElementVerificationResult(
        $elementType,
        Varien_Data_Collection_Db $collection,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel
    ) {
        /** @var $helper BL_CustomGrid_Helper_Grid */
        $helper = Mage::helper('customgrid/grid');
        $result = true;
        
        if ($elementType == 'block') {
            $result = $helper->verifyGridBlock($gridBlock, $gridModel);
        } elseif ($elementType == 'collection') {
            $result = $helper->verifyGridCollection($gridBlock, $gridModel);
        }
        
        return $result;
    }
    
    /**
     * Return the warning message displayable after a failed verification for the given element type on the given
     * block type
     * 
     * @param string $elementType Verified element type ("block" or "collection")
     * @param string $blockType Grid block type
     * @return string
     */
    protected function _getGridElementWarningMessage($elementType, $blockType)
    {
        $message = '';
        
        if ($elementType == 'block') {
            $message = 'The "%s" block type was not completely verified, some custom columns may not be working '
                . '(partially or fully)';
        } elseif ($elementType == 'collection') {
            $message = 'The collection for the "%s" block type was not completely verified, some custom columns may '
                . 'not be working (partially or fully)';
        }
        
        return ($message ? $this->getBaseHelper()->__($message, $blockType) : '');
    }
    
    /**
     * Return the error message displayable after a failed verification for the given element type on the given
     * block type
     * 
     * @param string $elementType Verified element type ("block" or "collection")
     * @param string $blockType Grid block type
     * @return string
     */
    protected function _getGridElementErrorMessage($elementType, $blockType)
    {
        $message = '';
        
        if ($elementType == 'block') {
            $message = 'The "%s" block type was not completely verified, the corresponding custom columns will not be '
                . 'applied';
        } elseif ($elementType == 'collection') {
            $message = 'The collection for "%s" block type was not completely verified, the corresponding custom '
                . 'columns will not be applied';
        }
        
        return ($message ? $this->getBaseHelper()->__($message, $blockType) : '');
    }
    
    /**
     * Handle the given result of a verification made on a grid element and a grid block of the given types,
     * according to the given behaviour, and return whether custom columns can still be applied
     * 
     * @param bool $result Verification result
     * @param string $elementType Grid element type
     * @param string $blockType Grid block type
     * @param string $behaviour Usable behaviour
     * @return bool
     */
    protected function _handleElementVerificationResult($result, $elementType, $blockType, $behaviour)
    {
        /** @var $session BL_CustomGrid_Model_Session */
        $session = Mage::getSingleton('customgrid/session');
        
        if ($result) {
            $this->_resetVerificationMessagesFlags($elementType, $blockType);
        } else {
            if ($behaviour == self::UNVERIFIED_BEHAVIOUR_WARNING) {
                $result = true;
            
                if ($this->_canDisplayVerificationMessage('warning', $elementType, $blockType)
                    && ($warningMessage = $this->_getGridElementWarningMessage($elementType, $blockType))) {
                    $session->addWarning($warningMessage);
                }
            } else {
                if ($this->_canDisplayVerificationMessage('error', $elementType, $blockType)
                    && ($errorMessage = $this->_getGridElementErrorMessage($elementType, $blockType))) {
                    $session->addError($errorMessage);
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Verify the sanity of the given grid element, and return whether custom columns can be applied to it
     * (the most safely possible). By default, it checks :
     * 
     * - For grid blocks, that :
     * _ the block is actually rewrited by our extension
     * _ the block inherits from the base Magento class corresponding to its type (if supported)
     * 
     * - For grid collections, that :
     * _ the collection inherits from the base Magento class corresponding to the block type (if supported)
     * 
     * @param string $elementType Verified element type ("block" or "collection")
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @return bool
     */
    protected function _verifyGridElement(
        $elementType,
        Varien_Data_Collection_Db $collection,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel
    ) {
        $blockType = $gridModel->getBlockType();
        $dataKey   = $elementType . '_verifications_cache/' . $blockType;
        
        if (!$this->hasData($dataKey)) {
            $behaviour = $this->_getUnverifiedElementBehaviour($elementType);
            
            if ($behaviour == self::UNVERIFIED_BEHAVIOUR_NONE) {
                $result = true;
            } else {
                $result = $this->_getGridElementVerificationResult($elementType, $collection, $gridBlock, $gridModel);
            }
            
            $this->setData(
                $dataKey,
                $this->_handleElementVerificationResult($result, $elementType, $blockType, $behaviour)
            );
        }
        
        return $this->getData($dataKey);
    }
    
    /**
     * Handle an error that occurred while applying the current custom column to a grid block.
     * By default, it adds the given corresponding error message to the session
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param string $message Error message
     * @return BL_CustomGrid_Model_Custom_Column_Applier
     */
    protected function _handleCustomColumnApplyError(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $message = ''
    ) {
        /** @var $session BL_CustomGrid_Model_Session */
        $session = Mage::getSingleton('customgrid/session');
        
        $name = $this->getCustomColumn()->getName();
        $message = $this->getBaseHelper()->__('The "%s" custom column could not be applied : "%s"', $name, $message);
        $session->addError($message);
        
        return $this;
    }
    
    /**
     * Apply the current custom column to the given grid block
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param string $columnBlockId Grid column block ID
     * @param string $columnIndex Grid column index
     * @param array $params Customization parameters values
     * @param Mage_Core_Model_Store $store Column store
     * @return BL_CustomGrid_Model_Custom_Column_Applier
     */
    protected function _applyCustomColumnToGridBlock(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $columnBlockId,
        $columnIndex,
        array $params,
        Mage_Core_Model_Store $store
    ) {
        $customColumn = $this->getCustomColumn();
        
        $customColumn->getCollectionHandler()
            ->prepareGridCollection(
                $gridBlock->getCollection(),
                $gridBlock,
                $gridModel,
                $columnBlockId,
                $columnIndex,
                $params,
                $store
            );
        
        $customColumn->applyToGridCollection(
            $gridBlock->getCollection(),
            $gridBlock,
            $gridModel,
            $columnBlockId,
            $columnIndex,
            $params,
            $store
        );
        
        return $this;
    }
    
    /**
     * Return the values usable to create a grid column block corresponding to the current custom column
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param string $columnBlockId Grid column block ID
     * @param string $columnIndex Grid column index
     * @param array $params Customization parameters values
     * @param Mage_Core_Model_Store $store Column store
     * @param BL_CustomGrid_Object|null $renderer Column collection renderer (if any)
     * @return array
     */
    public function getCustomColumnBlockValues(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $columnBlockId,
        $columnIndex,
        array $params,
        Mage_Core_Model_Store $store,
        BL_CustomGrid_Object $renderer = null
    ) {
        $customColumn = $this->getCustomColumn();
        $columnMethodParams   = array($gridBlock, $gridModel, $columnBlockId, $columnIndex, $params, $store);
        $rendererMethodParams = array($columnIndex, $store, $gridModel);
        $blockValues = array();
        $callbacks   = array(
            array(array($customColumn, 'getDefaultBlockValues'), $columnMethodParams),
            array(array($customColumn, 'getBlockValues'), $columnMethodParams),
            array(array($customColumn, 'getBlockParams'), array()),
            (is_object($renderer) ? array(array($renderer, 'getColumnBlockValues'), $rendererMethodParams) : false),
            array(array($customColumn, 'getForcedBlockValues'), $columnMethodParams),
        );
        
        foreach ($callbacks as $callback) {
            if (is_array($callback)) {
                $customColumn->setCurrentBlockValues($blockValues);
                
                if (is_array($callbackValues = call_user_func_array($callback[0], $callback[1]))) {
                    $blockValues = array_merge($blockValues, $callbackValues);
                }
            }
        }
        
        $customColumn->setCurrentBlockValues(array());
        return $blockValues;
    }
    
    /**
     * Apply the current custom column to the given grid block, and return the corresponding grid column block values,
     * or false if an error occurred
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel
     * @param string $columnBlockId Grid column block ID
     * @param string $columnIndex Grid column index
     * @param array $params Customization parameters values
     * @param Mage_Core_Model_Store $store Column store
     * @param BL_CustomGrid_Object|null $renderer Column collection renderer (if any)
     * @return array|false
     */
    public function applyCustomColumnToGridBlock(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $columnBlockId,
        $columnIndex,
        array $params,
        Mage_Core_Model_Store $store,
        BL_CustomGrid_Object $renderer = null
    ) {
        try {
            if (!$this->_verifyGridElement('block', $gridBlock->getCollection(), $gridBlock, $gridModel)) {
                Mage::throwException($this->getBaseHelper()->__('The grid block is not valid'));
            }
            if (!$this->_verifyGridElement('collection', $gridBlock->getCollection(), $gridBlock, $gridModel)) {
                Mage::throwException($this->getBaseHelper()->__('The grid collection is not valid'));
            }
            
            $this->_applyCustomColumnToGridBlock(
                $gridBlock,
                $gridModel,
                $columnBlockId,
                $columnIndex,
                $params,
                $store
            );
            
            $blockValues = $this->getCustomColumnBlockValues(
                $gridBlock,
                $gridModel,
                $columnBlockId,
                $columnIndex,
                $params,
                $store,
                $renderer
            );
            
        } catch (Exception $e) {
            $blockValues = false;
            $this->_handleCustomColumnApplyError($gridBlock, $gridModel, $e->getMessage());
        }
        
        return $blockValues;
    }
}
