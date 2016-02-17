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

class BL_CustomGrid_Model_Grid_Editor_Sentry extends BL_CustomGrid_Model_Grid_Editor_Worker_Abstract
{
    const ACTION_TYPE_CHECK_BASE_USER_EDIT_PERMISSIONS  = 'check_base_user_edit_permissions';
    const ACTION_TYPE_CHECK_CONTEXT_COLUMN_EDIT_ALLOWED = 'check_context_column_edit_allowed';
    const ACTION_TYPE_CHECK_CONTEXT_ADDITIONAL_USER_EDIT_PERMISSIONS = 'check_context_additional_user_edit_permissions';
    
    const BLOCK_TYPE_ALL = '__all__';
    
    public function getType()
    {
        return BL_CustomGrid_Model_Grid_Editor_Abstract::WORKER_TYPE_SENTRY;
    }
    
    /**
     * Return the ACL permissions required to edit values from the given grid block type
     * 
     * @param string $blockType Grid block type
     * @return string|array|null
     */
    protected function _getGridBlockEditRequiredAclPermissions($blockType)
    {
        $baseConfig = $this->getEditor()->getBaseConfig();
        return array_merge(
            (array) $baseConfig->getData('grid_block_edit_permissions/' . self::BLOCK_TYPE_ALL),
            (array) $baseConfig->getData('grid_block_edit_permissions/' . $blockType)
        );
    }
    
    /**
     * Default main callback for @see BL_CustomGrid_Model_Grid_Editor_Sentry::checkBaseUserEditPermissions()
     * 
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param mixed $previousReturnedValue Value returned by the previous callback
     * @return bool|string
     */
    public function _checkBaseUserEditPermissions(BL_CustomGrid_Model_Grid $gridModel, $previousReturnedValue)
    {
        $result = $previousReturnedValue;
        
        if (($result !== false) && !is_string($result)) {
            $blockType = $gridModel->getBlockType();
            $isAllowed = $gridModel->checkUserActionPermission(
                BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_COLUMNS_VALUES
            );
            
            if ($isAllowed && !is_null($permissions = $this->_getGridBlockEditRequiredAclPermissions($blockType))) {
                /** @var Mage_Admin_Model_Session $session */
                $session = Mage::getSingleton('admin/session');
                $permissions = array_filter((array) $permissions);
                
                foreach ($permissions as $permission) {
                    if (!$session->isAllowed($permission)) {
                        $isAllowed = false;
                        break;
                    }
                }
            }
            
            $result = $isAllowed;
        }
        
        return $result;
    }
    
    /**
     * Return whether the current user has all the necessary permissions to edit values from the given grid model
     * 
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block (if available)
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context (if available)
     * @return bool|string
     */
    public function checkBaseUserEditPermissions(
        BL_CustomGrid_Model_Grid $gridModel,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock = null,
        BL_CustomGrid_Model_Grid_Editor_Context $context = null
    ) {
        return $this->_runCallbackedAction(
            self::ACTION_TYPE_CHECK_BASE_USER_EDIT_PERMISSIONS,
            array(
                'gridModel' => $gridModel,
                'gridBlock' => $gridBlock,
                'context'   => $context,
            ),
            array($this, '_checkBaseUserEditPermissions')
        );
    }
    
    /**
     * Default main callback for @see BL_CustomGrid_Model_Grid_Editor_Sentry::checkContextColumnEditAllowed()
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @param mixed $previousReturnedValue Value returned by the previous callback
     * @return bool|string
     */
    public function _checkContextColumnEditAllowed(
        BL_CustomGrid_Model_Grid_Editor_Context $context,
        $previousReturnedValue
    ) {
        $result = $previousReturnedValue;
        
        if (($result !== false) && !is_string($result)) {
            $result = (($gridColumn = $context->getGridColumn()) && $gridColumn->isEditAllowed());
        }
        
        return $result;
    }
    
    /**
     * Return whether the edit is allowed for the edited column of the given editor context
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return bool|string
     */
    public function checkContextColumnEditAllowed(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        return $this->_runCallbackedAction(
            self::ACTION_TYPE_CHECK_CONTEXT_COLUMN_EDIT_ALLOWED,
            array('context' => $context),
            array($this, '_checkContextColumnEditAllowed'),
            $context
        );
    }
    
    /**
     * Default main callback for
     * @see BL_CustomGrid_Model_Grid_Editor_Sentry::checkContextAdditionalUserEditPermissions()
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @param mixed $previousReturnedValue Value returned by the previous callback
     * @return bool|string
     */
    public function _checkContextAdditionalUserEditPermissions(
        BL_CustomGrid_Model_Grid_Editor_Context $context,
        $previousReturnedValue
    ) {
        return (($previousReturnedValue !== false) && !is_string($previousReturnedValue));
    }
    
    /**
     * Return whether the current user has all the necessary additional permissions to perform the edit action
     * from the given editor context
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return bool|string
     */
    public function checkContextAdditionalUserEditPermissions(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        return $this->_runCallbackedAction(
            self::ACTION_TYPE_CHECK_CONTEXT_ADDITIONAL_USER_EDIT_PERMISSIONS,
            array('context' => $context),
            array($this, '_checkContextAdditionalUserEditPermissions'),
            $context
        );
    }
    
    /**
     * Return whether the edit action from the given editor context is allowed
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return bool|string
     */
    public function isEditAllowedForContext(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        if ((($result = $this->checkBaseUserEditPermissions($context->getGridModel(), null, $context)) !== true)
            || (($result = $this->checkContextColumnEditAllowed($context)) !== true)
            || (($result = $this->checkContextAdditionalUserEditPermissions($context)) !== true)) {
            return $result;
        }
        return true;
    }
}
