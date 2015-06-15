<?php

/**
 * MageWorx
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MageWorx EULA that is bundled with
 * this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.mageworx.com/LICENSE-1.0.html
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@mageworx.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension
 * to newer versions in the future. If you wish to customize the extension
 * for your needs please refer to http://www.mageworx.com/ for more information
 * or send an email to sales@mageworx.com
 *
 * @category   MageWorx
 * @package    MageWorx_CustomOptions
 * @copyright  Copyright (c) 2009 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */

/**
 * Custom Options extension
 *
 * @category   MageWorx
 * @package    MageWorx_CustomOptions
 * @author     MageWorx Dev Team <dev@mageworx.com>
 */
class MageWorx_CustomOptions_Model_Mysql4_Product_Option extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Option {

    protected function _afterSave(Mage_Core_Model_Abstract $object) {
        $table = $this->getTable('customoptions/option_description');
        $storeId = Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID;
        if ($object->getStoreId() != $storeId) {
            $storeId = $object->getStoreId();
        }

        if (!$object->getData('scope', 'description')) {
            $select = $this->_getReadAdapter()->select()
                    ->from($table)
                    ->where('option_id = ?', $object->getId())
                    ->where('store_id = ?', $storeId);

            if ($this->_getReadAdapter()->fetchOne($select)) {
                $this->_getWriteAdapter()->update(
                        $table, array('description' => $object->getDescription()), 'option_id = ' . $object->getId() . ' AND store_id = ' . $storeId
                );
            } else {
                $this->_getWriteAdapter()->insert(
                        $table, array(
                    'option_id' => $object->getId(),
                    'store_id' => $storeId,
                    'description' => $object->getDescription()
                        )
                );
            }
        } elseif ($object->getData('scope', 'description')) {
            $this->_getWriteAdapter()->delete(
                    $table, 'option_id = ' . $object->getId() . ' AND store_id = ' . $storeId
            );
        }
        return parent::_afterSave($object);
    }

    public function getTitle($typeId, $storeId) {
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        $table = $tablePrefix . 'catalog_product_option_type_title';
        $select = $this->_getReadAdapter()->select()
                ->from($table)
                ->where('option_type_id = ?', $typeId)
                ->where('store_id = ?', $storeId);

        $row = $this->_getReadAdapter()->fetchRow($select);

        return $row['title'];
    }

    public function setTitle($typeId, $storeId, $title) {
        $table = $this->getTable('catalog/product_option_type_title');

        if ($this->_getWriteAdapter()->update(
                        $this->getTable('catalog/product_option_type_title'), array('title' => $title), $this->_getWriteAdapter()->quoteInto('option_type_id = ?', $typeId))) {
            return true;
        }

        return false;
    }

}
