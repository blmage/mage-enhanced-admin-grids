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
 * @copyright  Copyright (c) 2011 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Grid_Type
    extends BL_CustomGrid_Model_Config_Abstract
{   
    public function getConfigType()
    {
        return BL_CustomGrid_Model_Config::TYPE_GRID_TYPES;
    }
    
    public function getTypeInstanceByCode($code, $params=null)
    {
        return parent::getElementInstanceByCode($code, $params);
    }
    
    public function getTypesInstances()
    {
        $types = array();
        foreach ($this->getElementsArray() as $type) {
            if ($types[$type['code']] = $this->getElementInstanceByCode($type['code'])) {
                $types[$type['code']]->setCode($type['code']);
            }
        }
        return $types;
    }
    
    public function getTypesAsOptionHash()
    {
        $types = array();
        foreach ($this->getElementsArray() as $type) {
            $types[$type['code']] = $type['name'];
        }
        return $types;
    }
}