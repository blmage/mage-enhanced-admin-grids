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
 * @copyright  Copyright (c) 2014 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Grid_Type_Product_Rating extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    /**
     * @return string[]|string
     */
    protected function _getSupportedBlockTypes()
    {
        return array('adminhtml/rating_grid');
    }

    /**
     * @param string $blockType
     * @return array
     */
    protected function _getBaseEditableFields($blockType)
    {
        $fields = array(
            'rating_code' => array(
                'type'     => 'text',
                'required' => true,
            ),
            'position' => array(
                'type'       => 'text',
                'required'   => true,
                'form_class' => 'validate-number',
            ),
        );

        return $fields;
    }

    protected function _getEntityRowIdentifiersKeys($blockType)
    {
        return array('rating_id');
    }

    protected function _loadEditedEntity($blockType, BL_CustomGrid_Object $config, array $params, $entityId)
    {
        return Mage::getModel('rating/rating')->load($entityId);
    }

    protected function _getEditRequiredAclPermissions($blockType)
    {
        return 'catalog/reviews_ratings/ratings';
    }
}
