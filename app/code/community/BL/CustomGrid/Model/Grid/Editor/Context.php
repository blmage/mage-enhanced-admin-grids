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

/**
 * @method BL_CustomGrid_Model_Grid getGridModel() Return the current grid model
 * @method string getBlockType() Return the type of the grid block to whom the edited column belongs
 * @method string getValueId() Return the ID of the edited value
 * @method string getValueOrigin() Return the origin of the edited value (@see BL_CustomGrid_Model_Grid_Editor_Abstract)
 * @method BL_CustomGrid_Model_Grid_Editor_Value_Config getValueConfig() Return the editor config for the edited value
 * @method BL_CustomGrid_Object getRequestParams() Return the edit request parameters, wrapped in an object
 * @method mixed getEditedEntity() Return the edited entity
 * @method string|null getOriginGridTypeCode() Return the code of the active grid type at the origin of the edit request
 */
class BL_CustomGrid_Model_Grid_Editor_Context extends BL_CustomGrid_Object
{
    /**
     * Values that must be defined in order for a context object to be valid :
     * when one of those values is requested, it must be present in the data
     * 
     * @var array
     */
    static protected $_requiredKeys = array(
        'grid_model',
        'block_type',
        'value_id',
        'value_origin',
        'value_config',
        'request_params',
        'edited_entity',
    );
    
    public function getData($key = '', $index = null)
    {
        if (($key !== '')
            && in_array($key, self::$_requiredKeys)
            && !$this->hasData($key)) {
            Mage::throwException('Missing required value in editor context : "' . $key . '"');
        }
        return parent::getData($key, $index);
    }
    
    /**
     * Return the identifying key for this context
     * 
     * @return string
     */
    public function getKey()
    {
        if (!$this->hasData('key')) {
            $this->setData(
                'key',
                $this->getData('grid_model')->getId()
                . '_' . $this->getData('grid_model')->getProfileId()
                . '_' . $this->getData('value_id')
                . '_' . $this->getData('value_origin')
            );
        }
        return $this->_getData('key');
    }
    
    /**
     * Return the edited grid column
     * 
     * @return BL_CustomGrid_Model_Grid_Column|null
     */
    public function getGridColumn()
    {
        if (!$this->hasData('grid_column')) {
            $requestParams = $this->getRequestParams();
            
            if ($requestParams->hasData('additional/column_id')) {
                $gridColumn = $this->getGridModel()->getColumnById($requestParams->getData('additional/column_id'));
            } else {
                $gridColumn = null;
            }
            
            $this->setData('grid_column', $gridColumn); 
        }
        return $this->_getData('grid_column');
    }
    
    /**
     * Return whether the context edited value is an attribute value
     * 
     * @return bool
     */
    public function isAttributeValueContext()
    {
        return ($this->getValueOrigin() == BL_CustomGrid_Model_Grid_Editor_Abstract::EDITABLE_TYPE_ATTRIBUTE);
    }
    
    /**
     * Return whether the context edited value is a field value
     *
     * @return bool
     */
    public function isFieldValueContext()
    {
        return ($this->getValueOrigin() == BL_CustomGrid_Model_Grid_Editor_Abstract::EDITABLE_TYPE_FIELD);
    }
    
    /**
     * Return whether the context edited value is a custom column value
     *
     * @return bool
     */
    public function isCustomColumnValueContext()
    {
        return ($this->getValueOrigin() == BL_CustomGrid_Model_Grid_Editor_Abstract::EDITABLE_TYPE_CUSTOM_COLUMN);
    }
    
    /**
     * Return the additional parameters from the context request
     * 
     * @return array
     */
    public function getRequestAdditionalParams()
    {
        return $this->getDataSetDefault('request_params/additional', array());
    }
    
    /**
     * Return the global parameters from the context request
     * 
     * @return array
     */
    public function getRequestGlobalParams()
    {
        return $this->getDataSetDefault('request_params/global', array());
    }
    
    /**
     * Return the IDs parameters from the context request
     * 
     * @return array
     */
    public function getRequestIdsParams()
    {
        return $this->getDataSetDefault('request_params/ids', array());
    }
    
    /**
     * Return the values parameters from the context request
     * 
     * @return array
     */
    public function getRequestValuesParams()
    {
        return $this->getDataSetDefault('request_params/values', array());
    }
    
    /**
     * Return the name of the context form field
     * 
     * @return mixed
     */
    public function getFormFieldName()
    {
        return $this->getValueConfig()->getFormFieldName();
    }
}
