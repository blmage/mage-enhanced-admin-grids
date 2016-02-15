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

abstract class BL_CustomGrid_Model_Column_Renderer_Attribute_Abstract extends BL_CustomGrid_Model_Column_Renderer_Abstract
{
    public function getColumnType()
    {
        return 'attribute';
    }
    
    /**
     * Return whether this renderer can be used to render columns based on the given attribute
     * 
     * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute model
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @return bool
     */
    abstract public function isAppliableToAttribute(
        Mage_Eav_Model_Entity_Attribute $attribute,
        BL_CustomGrid_Model_Grid $gridModel
    );
    
    /**
     * Return suitable values to create a grid column block based on this renderer and the given attribute
     * 
     * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute model
     * @param Mage_Core_Model_Store $store Grid store
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @return array
     */
    abstract public function getColumnBlockValues(
        Mage_Eav_Model_Entity_Attribute $attribute,
        Mage_Core_Model_Store $store,
        BL_CustomGrid_Model_Grid $gridModel
    );
}
