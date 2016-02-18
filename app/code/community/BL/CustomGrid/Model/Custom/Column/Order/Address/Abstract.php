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

abstract class BL_CustomGrid_Model_Custom_Column_Order_Address_Abstract extends BL_CustomGrid_Model_Custom_Column_Simple_Table
{
    protected function _prepareConfig()
    {
        $this->setExcludedVersions('1.4.0.*');
        return parent::_prepareConfig();
    }
    
    abstract public function getAddressType();
    
    protected function _getAppliedFlagKey(
        $columnIndex,
        array $params,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection,
        $tableName
    ) {
        return $tableName . '/' . $this->getAddressType();
    }
    
    public function getTableName()
    {
        return 'sales/order_address';
    }
    
    public function getJoinConditionMainFieldName()
    {
        return (($field = parent::getJoinConditionMainFieldName()) ? $field : 'entity_id');
    }
    
    public function getJoinConditionTableFieldName()
    {
        return (($field = parent::getJoinConditionTableFieldName()) ? $field : 'parent_id');
    }
    
    public function getTableFieldName()
    {
        return $this->getConfigParam('address_field_name');
    }
    
    protected function _getAdditionalJoinConditions(
        $columnIndex,
        array $params,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection,
        $mainAlias,
        $tableAlias
    ) {
        /** @var $adapter Zend_Db_Adapter_Abstract */
        list($adapter, $qi) = $this->getCollectionHandler()->getCollectionAdapter($collection, true);
        return array($adapter->quoteInto($qi($tableAlias . '.address_type') . ' = ?', $this->getAddressType()));
    }
    
    public function getGridColumnEditorConfig(
        BL_CustomGrid_Model_Grid_Column $gridColumn,
        BL_CustomGrid_Model_Grid_Editor_Value_Config_Builder $configBuilder
    ) {
        $addressType  = $this->getAddressType();
        $addressField = $this->getTableFieldName();
        
        if ($this->getBaseHelper()->isMageVersionLesserThan(1, 5)
            || in_array($addressField, array('country_id', 'region'))) {
            return false;
        }
        
        return $this->_buildGridColumnEditableFieldConfig(
            $gridColumn,
            $configBuilder,
            array(
                'form' => array(
                    'block_type' => 'customgrid/widget_grid_editor_form_order_address',
                    'is_in_grid' => ($addressField != 'street'),
                ),
                'form_field' => array(
                    'name' => $addressField,
                    'address_type'  => $addressType,
                    'address_field' => $addressField,
                )
            )
        );
    }
    
    public function getEditorContextAdditionalCallbacks(
        BL_CustomGrid_Model_Grid_Editor_Context $context,
        BL_CustomGrid_Model_Grid_Editor_Callback_Manager $callbackManager
    ) {
        return array(
            $callbackManager->getCallbackFromCallable(
                array($this, 'applyUserEditedValueToEditedOrderAddress'),
                BL_CustomGrid_Model_Grid_Editor_Abstract::WORKER_TYPE_ENTITY_UPDATER,
                BL_CustomGrid_Model_Grid_Editor_Entity_Updater::ACTION_TYPE_APPLY_USER_EDITED_VALUE_TO_EDITED_ENTITY,
                BL_CustomGrid_Model_Grid_Editor_Callback::POSITION_MAIN,
                BL_CustomGrid_Model_Grid_Editor_Callback::PRIORITY_EXTERNAL_HIGHER,
                true
            ),
            $callbackManager->getCallbackFromCallable(
                array($this, 'saveContextEditedOrderAddress'),
                BL_CustomGrid_Model_Grid_Editor_Abstract::WORKER_TYPE_ENTITY_UPDATER,
                BL_CustomGrid_Model_Grid_Editor_Entity_Updater::ACTION_TYPE_SAVE_CONTEXT_EDITED_ENTITY,
                BL_CustomGrid_Model_Grid_Editor_Callback::POSITION_MAIN,
                BL_CustomGrid_Model_Grid_Editor_Callback::PRIORITY_EXTERNAL_HIGHER
            ),
            $callbackManager->getCallbackFromCallable(
                array($this, 'getRenderableContextEditedValue'),
                BL_CustomGrid_Model_Grid_Editor_Abstract::WORKER_TYPE_VALUE_RENDERER,
                BL_CustomGrid_Model_Grid_Editor_Value_Renderer::ACTION_TYPE_GET_RENDERABLE_CONTEXT_EDITED_VALUE,
                BL_CustomGrid_Model_Grid_Editor_Callback::POSITION_MAIN,
                BL_CustomGrid_Model_Grid_Editor_Callback::PRIORITY_EXTERNAL_HIGHER
            ),
        );
    }
    
    /**
     * Return the edited order address from the given edited order
     * 
     * @param Mage_Sales_Model_Order $editedOrder Edited order
     * @return Mage_Sales_Model_Order_Address
     */
    protected function _getEditedOrderAddress(Mage_Sales_Model_Order $editedOrder)
    {
        return ($this->getAddressType() == Mage_Sales_Model_Order_Address::TYPE_SHIPPING)
            ? $editedOrder->getShippingAddress()
            : $editedOrder->getBillingAddress();
    }
    
    /**
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::applyUserEditedValueToEditedEntity()
     * 
     * @param Mage_Sales_Model_Order $editedEntity Edited order
     * @param mixed $userValue User-edited value
     * @return bool
     */
    public function applyUserEditedValueToEditedOrderAddress(
        Mage_Sales_Model_Order $editedEntity,
        $userValue
    ) {
        $this->_getEditedOrderAddress($editedEntity)->setData($this->getTableFieldName(), $userValue);
        return true;
    }
    
    /**
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::saveContextEditedEntity()
     * 
     * @param Mage_Sales_Model_Order $editedEntity Edited order
     * @return bool
     * @throws Exception
     */
    public function saveContextEditedOrderAddress(Mage_Sales_Model_Order $editedEntity)
    {
        $this->_getEditedOrderAddress($editedEntity)->implodeStreetAddress()->save();
        return true;
    }
    
    /**
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Value_Renderer::getRenderableContextEditedValue()
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @param BL_CustomGrid_Object $transport Transport object used to hold the renderable value
     */
    public function getRenderableContextEditedValue(
        BL_CustomGrid_Model_Grid_Editor_Context $context,
        BL_CustomGrid_Object $transport
    ) {
        $transport->setData(
            'value',
            $this->_getEditedOrderAddress($context->getEditedEntity())->getData($this->getTableFieldName())
        );
    }
}
