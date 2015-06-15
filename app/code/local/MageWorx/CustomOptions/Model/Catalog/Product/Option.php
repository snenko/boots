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
class MageWorx_CustomOptions_Model_Catalog_Product_Option extends Mage_Catalog_Model_Product_Option {

    protected function _construct() {
        parent::_construct();
        $this->_init('customoptions/product_option');
    }

    private function _prepareOptions($options) {
        if (isset($options) && is_array($options)) {
            foreach ($options as $key => $value) {
                unset($options[$key]['option_id']);
                if (isset($value['values']) && is_array($value['values'])) {
                    foreach ($value['values'] as $vKey => $val) {
                        $options[$key]['values'][$vKey]['option_type_id'] = '-1';
                    }
                }
            }
        }        
        return $options;
    }

    public function deleteProductsFromOptions(array $options, array $productIds, Varien_Object $group) {
        if (isset($productIds) && is_array($productIds)) {
            $optionIds = array();
            $relation = Mage::getResourceSingleton('customoptions/relation');
            $productModel = Mage::getModel('catalog/product');

            $this->removeProductOptions($group->getId());

            $productOption = Mage::getModel('catalog/product_option');
            $productModel = Mage::getModel('catalog/product');

            foreach ($productIds as $productId) {
                $product = $productModel->load($productId);

                $productOption->setProduct($product);
                $options = $productOption->getProduct()->getOptions();
                if (empty($options)) {
                    $this->setHasOptions($productId, 0);
                }

                $relation->changeHasOptionsKey($productId);
            }
            $relation->deleteGroup($group->getId());
        }
        return $optionIds;
    }

    public function saveProductOptions(array $options, array $productIds, Varien_Object $group, $remove = true, $place = 'apo') {        
        if (isset($productIds) && is_array($productIds)) {
            $optionIds = array();
            $relation = Mage::getResourceSingleton('customoptions/relation');
            $productModel = Mage::getModel('catalog/product');

            if ($remove) {
                $this->removeProductOptions($group->getId());
                $relation->deleteGroup($group->getId());
            }

            $groupId = $group->getId();
            $groupIsActive = $group->getIsActive();

            $connection = Mage::getSingleton('core/resource')->getConnection('core_write');

            $tablePrefix = (string) Mage::getConfig()->getTablePrefix();

            $condition = '';
            if ($place == 'product') {
                $condition = $place == 'product' ? ' AND product_id = ' . current($productIds) : '';
            }

            $select = $connection->select()->from($tablePrefix . 'custom_options_relation')->where('group_id = ' . $groupId . $condition);
            $optionRelations = $connection->fetchAll($select);

            foreach ($optionRelations as $optionRelation) {
                $connection->delete($tablePrefix . 'catalog_product_option', 'option_id = ' . $optionRelation['option_id']);
                $connection->delete($tablePrefix . 'catalog_product_option_title', 'option_id = ' . $optionRelation['option_id']);
                $connection->delete($tablePrefix . 'catalog_product_option_price', 'option_id = ' . $optionRelation['option_id']);
                $connection->delete($tablePrefix . 'custom_options_option_description', 'option_id = ' . $optionRelation['option_id']);

                $select = $connection->select()->from($tablePrefix . 'catalog_product_option_type_value')->where('option_id = ' . $optionRelation['option_id']);
                $values = $connection->fetchAll($select);

                foreach ($values as $value) {
                    $connection->delete($tablePrefix . 'catalog_product_option_type_price', 'option_type_id = ' . $value['option_type_id']);
                    $connection->delete($tablePrefix . 'catalog_product_option_type_title', 'option_type_id = ' . $value['option_type_id']);
                }
                $connection->delete($tablePrefix . 'catalog_product_option_type_value', 'option_id = ' . $optionRelation['option_id']);
            }

            $connection->delete($tablePrefix . 'custom_options_relation', 'group_id = ' . $groupId . $condition);

            $storeId = Mage::app()->getStore()->getId();
            foreach ($productIds as $productId) {
                $connection->update($tablePrefix . 'catalog_product_entity', array('has_options' => 1), 'entity_id = ' . $productId);

                $this->setOptions($options);
                $options = $this->_prepareOptions($options);
                if (isset($options) && is_array($options)) {
                    foreach ($options as $option) {

                        if (isset($option['is_delete']) && $option['is_delete'] == 1) {
                            $connection->delete($tablePrefix . 'catalog_product_option', 'option_id = ' . $option['id']);
                            $connection->delete($tablePrefix . 'catalog_product_option_price', 'option_id = ' . $option['id']);
                            $connection->delete($tablePrefix . 'catalog_product_option_title', 'option_id = ' . $option['id']);
                            $connection->delete($tablePrefix . 'custom_options_relation', 'option_id = ' . $option['id']);
                            $connection->delete($tablePrefix . 'custom_options_option_description', 'option_id = ' . $option['id']);

                            $select = $connection->select()->from($tablePrefix . 'catalog_product_option_type_value')->where('option_id = ' . $option['id'])->order('option_type_id DESC');
                            $values = $connection->fetchAll($select);
                            foreach ($values as $value) {
                                $connection->delete($tablePrefix . 'catalog_product_option_type_price', 'option_type_id = ' . $value['option_type_id']);
                                $connection->delete($tablePrefix . 'catalog_product_option_type_title', 'option_type_id = ' . $value['option_type_id']);
                            }
                            $connection->delete($tablePrefix . 'catalog_product_option_type_value', 'option_id = ' . $option['id']);
                            continue;
                        }
                        
                        $option['customer_groups'] = isset($option['customer_groups']) ? implode(',', $option['customer_groups']) : '';
                        
                        $optionData = array(
                            'type' => $option['type'],
                            'is_require' => $option['is_require'],
                            'sku' => isset($option['sku']) ? $option['sku'] : '',
                            'max_characters' => isset($option['max_characters']) ? $option['max_characters'] : null,
                            'file_extension' => isset($option['file_extension']) ? $option['file_extension'] : null,
                            'image_size_x' => isset($option['image_size_x']) ? $option['image_size_x'] : 0,
                            'image_size_y' => isset($option['image_size_y']) ? $option['image_size_y'] : 0,
                            'product_Id' => $productId,
                            'sort_order' => $option['sort_order'],
                            'customoptions_status' => $groupIsActive,
                            'customoptions_is_onetime' => $option['customoptions_is_onetime'],
                            'customer_groups' => $option['customer_groups']
                        );
                        if (isset($option['id'])) {
                            $optionTitle = array(
                                'store_id' => $storeId,
                                'title' => $option['title']
                            );
                            $connection->insert($tablePrefix . 'catalog_product_option', $optionData);
                            $optionId = $connection->lastInsertId($tablePrefix . 'catalog_product_option');
                            if ($option['type'] == 'field') {
                                if (Mage::helper('customoptions')->isCustomOptionsFile($groupId, $option['id'])) {
                                    $connection->update($tablePrefix . 'catalog_product_option', array('image_path' => $groupId . DS . $option['id'] . DS), 'option_id = ' . $optionId);
                                }
                            }
                            $optionTitle['option_id'] = $optionId;
                            $connection->insert($tablePrefix . 'catalog_product_option_title', $optionTitle);

                            $optionDesc = array(
                                'option_id' => $optionId,
                                'store_id' => $storeId,
                                'description' => $option['description']
                            );

                            $connection->insert($tablePrefix . 'custom_options_option_description', $optionDesc);

                            $optionRelation = array(
                                'option_id' => $optionId,
                                'group_id' => $groupId,
                                'product_id' => $productId,
                            );
                            $connection->insert($tablePrefix . 'custom_options_relation', $optionRelation);
                        } else {
                            $connection->insert($tablePrefix . 'catalog_product_option', $optionData);
                            $optionId = $connection->lastInsertId($tablePrefix . 'catalog_product_option');
                            $optionTitle = array(
                                'option_id' => $optionId,
                                'store_id' => $storeId,
                                'title' => $option['title']
                            );
                            $connection->insert($tablePrefix . 'catalog_product_option_title', $optionTitle);

                            $optionDesc = array(
                                'option_id' => $optionId,
                                'store_id' => $storeId,
                                'description' => $option['description']
                            );
                            $connection->insert($tablePrefix . 'custom_options_option_description', $optionDesc);

                            $optionRelation = array(
                                'group_id' => $groupId,
                                'product_id' => $productId,
                                'option_id' => $optionId
                            );
                            $connection->insert($tablePrefix . 'custom_options_relation', $optionRelation);
                        }

                        if (isset($option['image_delete'][$optionId]) && $option['image_delete'][$optionId] == $optionId) {
                            Mage::getSingleton('multifees/option')->removeOptionFile($optionId, false);
                        }

                        $this->_uploadImage('file_' . $optionId, $optionId);

                        $optionId = isset($optionId) ? $optionId : $option['id'];
                        if (isset($option['values']) && is_array($option['values'])) {
                            foreach ($option['values'] as $value) {
                                $select = $connection->select()->from($tablePrefix . 'catalog_product_option_type_value')->where('option_id = ' . $optionId);
                                $optionTypeValues = $connection->fetchAll($select);
                                if (is_array($optionTypeValues) && count($optionTypeValues) > 0) {
                                    foreach ($optionTypeValues as $optionTypeValue) {
                                        $connection->delete($tablePrefix . 'catalog_product_option_type_price', 'option_type_id = ' . $optionTypeValue['option_type_id']);
                                        $connection->delete($tablePrefix . 'catalog_product_option_type_title', 'option_type_id = ' . $optionTypeValue['option_type_id']);
                                    }
                                }
                                $connection->delete($tablePrefix . 'catalog_product_option_type_value', 'option_id = ' . $optionId);
                            }
                            $defaultArray = isset($option['default']) ? $option['default'] : array();
                            foreach ($option['values'] as $key => $value) {
                                $optionValue = array(
                                    'option_id' => $optionId,
                                    'sku' => $value['sku'],
                                    'sort_order' => $value['sort_order'],
                                    'customoptions_qty' => isset($value['customoptions_qty']) ? $value['customoptions_qty'] : '',
                                    'default' => array_search($key, $defaultArray) !== false ? 1 : 0
                                );
                                $connection->insert($tablePrefix . 'catalog_product_option_type_value', $optionValue);

                                $optionTypeId = $connection->lastInsertId($tablePrefix . 'catalog_product_option_type_value');
                                if (Mage::helper('customoptions')->isCustomOptionsFile($groupId, $option['id'], $key)) {
                                    $connection->update($tablePrefix . 'catalog_product_option_type_value', array('image_path' => $groupId . DS . $option['id'] . DS . $key), 'option_type_id = ' . $optionTypeId);
                                }
                                $optionTypePrice = array(
                                    'option_type_id' => $optionTypeId,
                                    'store_id' => $storeId,
                                    'price' => $value['price'],
                                    'price_type' => $value['price_type']
                                );
                                $connection->insert($tablePrefix . 'catalog_product_option_type_price', $optionTypePrice);

                                $optionTypeTitle = array(
                                    'option_type_id' => $optionTypeId,
                                    'store_id' => $storeId,
                                    'title' => $value['title']
                                );
                                $connection->insert($tablePrefix . 'catalog_product_option_type_title', $optionTypeTitle);
                            }
                        } else {
                            if ('field' == $option['type'] || 'area' == $option['type'] || 'file' == $option['type'] || 'date' == $option['type']) {
                                $connection->delete($tablePrefix . 'catalog_product_option_price', 'option_id = ' . $optionId . ' AND store_id = ' . $storeId);
                                $optionPrice = array(
                                    'option_id' => $optionId,
                                    'store_id' => $storeId,
                                    'price' => $option['price'],
                                    'price_type' => $option['price_type']
                                );
                                $connection->insert($tablePrefix . 'catalog_product_option_price', $optionPrice);

                                $connection->delete($tablePrefix . 'custom_options_option_description', 'option_id = ' . $optionId . ' AND store_id = ' . $storeId);
                                $optionDesc = array(
                                    'option_id' => $optionId,
                                    'store_id' => $storeId,
                                    'description' => $option['description']
                                );
                                $connection->insert($tablePrefix . 'custom_options_option_description', $optionDesc);
                            }
                        }

                        if ($this->getId()) {
                            $optionIds[$productId][] = $this->getId();
                        }
                    }
                }
                $select = $connection->select()->from($tablePrefix . 'catalog_product_option')->where('product_id = ' . $productId);
                $tempOptions = $connection->fetchAll($select);
                if (empty($tempOptions) || $groupIsActive == 2) {
                    $connection->update(
                            $tablePrefix . 'catalog_product_entity', array('has_options' => 0), 'entity_id = ' . $productId
                    );
                }
            }
        } else {
            if ($remove) {
                $this->removeProductOptions($group->getId());
                $relation = Mage::getResourceSingleton('customoptions/relation');
                $relation->deleteGroup($group->getId());
            }
        }
        return $optionIds;
    }

    public function removeProductOptions($groupId, $id = null) {
        $relation = Mage::getResourceSingleton('customoptions/relation');
        if (is_null($id)) {
            $productIds = $relation->getProductIds($groupId);
            if (isset($productIds) && is_array($productIds)) {
                foreach ($productIds as $productId) {
                    $relationOptionIds = $relation->getOptionIds($groupId, $productId);
                    $this->_removeRelationOptions($relationOptionIds);
                }
            }
        } else {
            $relationOptionIds = $relation->getOptionIds($groupId, $id);
            $this->_removeRelationOptions($relationOptionIds);
        }
    }

    private function _removeOptionDescription($id) {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        $connection->delete($tablePrefix . 'custom_options_option_description', 'option_id = ' . $id);
    }

    private function _removeRelationOptions($relationOptionIds) {
        if (isset($relationOptionIds) && is_array($relationOptionIds)) {
            foreach ($relationOptionIds as $id) {
                $this->_removeOptionDescription($id);
                $this->getValueInstance()->deleteValue($id);
                $this->deletePrices($id);
                $this->deleteTitles($id);
                $this->setId($id)->delete();
            }
        }
    }

    private function _uploadImage($keyFile, $optionId, $valueId = false) {                
        if (isset($_FILES[$keyFile]['name']) && $_FILES[$keyFile]['name'] != '') {
            try {
                Mage::helper('customoptions')->deleteValueFile(null, $optionId, $valueId);

                $uploader = new Varien_File_Uploader($keyFile);
                $uploader->setAllowedExtensions(array('jpg', 'jpeg', 'gif', 'png'));
                $uploader->setAllowRenameFiles(false);
                $uploader->setFilesDispersion(false);


                $uploader->save(Mage::helper('customoptions')->getCustomOptionsPath(false, $optionId, $valueId), $_FILES[$keyFile]['name']);
                return true;
            } catch (Exception $e) {
                if ($e->getMessage()) {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                }
            }
        }
    }

    public function saveOptions() {
        $options = $this->getOptions();        
        $newOptions = Array();

        $post = Mage::app()->getRequest()->getPost();
        $productId = $this->getProduct()->getId();
        $relation = Mage::getSingleton('customoptions/relation');

        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        if (isset($post['image_delete'])) {
            $productOption = Mage::getModel('catalog/product_option');
            foreach ($post['image_delete'] as $key1 => $optionId) {
                if (is_array($optionId)) {
                    foreach ($optionId as $vId => $oId) {
                        $connection->update($tablePrefix . 'catalog_product_option_type_value', array('image_path' => ''), 'option_type_id = ' . $vId);
                    }
                } else {
                	$connection->update($tablePrefix . 'catalog_product_option', array('image_path' => ''), 'option_id = ' . $optionId);
                }
            }
        }

        //$productOption = Mage::getModel('catalog/product_option');        

        foreach ($options as $option) {
            if (isset($option['option_id'])) {
                $connection->update($tablePrefix . 'catalog_product_option_type_value', array('default' => 0), 'option_id = ' . $option['option_id']);
                if (isset($option['default'])) {
                    foreach ($option['default'] as $value) {
                        $connection->update($tablePrefix . 'catalog_product_option_type_value', array('default' => 1), 'option_type_id = ' . $value);
                    }
                }
            }
        }
        
        
        if (Mage::helper('customoptions')->isCustomerGroupsEnabled()) {
            $options = $this->getOptions();
            foreach ($options as $key => $option) {
                if (isset($option['customer_groups'])) {
                    $options[$key]['customer_groups'] = implode(',', $option['customer_groups']);
                }
            }
            $this->setOptions($options);
        }
        
        //print_r($this->getOptions()); exit;
        
        // original code m1510 parent::saveOptions();
        foreach ($this->getOptions() as $option) {
            $this->setData($option)
                ->setData('product_id', $this->getProduct()->getId())
                ->setData('store_id', $this->getProduct()->getStoreId());

            if ($this->getData('option_id') == '0') {
                $this->unsetData('option_id');
            } else {
                $this->setId($this->getData('option_id'));
            }
            $isEdit = (bool)$this->getId()? true:false;

            if ($this->getData('is_delete') == '1') {
                if ($isEdit) {
                    $this->getValueInstance()->deleteValue($this->getId());
                    $this->deletePrices($this->getId());
                    $this->deleteTitles($this->getId());
                    $this->delete();
                    Mage::helper('customoptions')->deleteValueFile(null, $this->getId(), false);
                }
            } else {
                if ($this->getData('previous_type') != '') {
                    $previousType = $this->getData('previous_type');
                    //if previous option has dfferent group from one is came now need to remove all data of previous group
                    if ($this->getGroupByType($previousType) != $this->getGroupByType($this->getData('type'))) {

                        switch ($this->getGroupByType($previousType)) {
                            case self::OPTION_GROUP_SELECT:
                                $this->unsetData('values');
                                if ($isEdit) {
                                    $this->getValueInstance()->deleteValue($this->getId());
                                }
                                break;
                            case self::OPTION_GROUP_FILE:
                                $this->setData('file_extension', '');
                                $this->setData('image_size_x', '0');
                                $this->setData('image_size_y', '0');
                                break;
                            case self::OPTION_GROUP_TEXT:
                                $this->setData('max_characters', '0');
                                break;
                            case self::OPTION_GROUP_DATE:
                                break;
                        }
                        if ($this->getGroupByType($this->getData('type')) == self::OPTION_GROUP_SELECT) {
                            $this->setData('sku', '');
                            $this->unsetData('price');
                            $this->unsetData('price_type');
                            if ($isEdit) {
                                $this->deletePrices($this->getId());
                            }
                        }
                    }
                }
                $this->save();
                
                if (!isset($option['option_id']) || !$option['option_id']) {                                        
                    $values = $this->getValues();
                    //print_r($values); exit;
                    $option['option_id']=$this->getId();                    
                }    
                
                switch ($option['type']) {
                    case 'field':
                    case 'area':
                        if ($this->_uploadImage('file_' . $option['id'], $option['option_id'])) {                            
                            $this->setImagePath('options' . DS . $option['option_id'] . DS)->save();
                        }
                        break;
                    case 'drop_down':
                    case 'radio':
                    case 'checkbox':
                    case 'multiple':
//                        foreach ($option['values'] as $value) {
//                            if (isset($option['option_id'])) {
//                                if ($this->_uploadImage('file_'.$option['id'].'-'.$value['option_type_id'], $option['option_id'], $value['option_type_id'])) {
//                                    $connection->update($tablePrefix . 'catalog_product_option_type_value', array('image_path' => 'options' . DS . $option['option_id'] . DS . $value['option_type_id'] . DS), 'option_type_id = ' . $value['option_type_id']);
//                                }
//                            }
//                        }
//                        break;
                    case 'file':
                    case 'date':
                    case 'date_time':
                    case 'time':
                        // no image
                        if (isset($option['option_id'])) {
                            Mage::helper('customoptions')->deleteValueFile(null, $option['option_id'], false);
                            $this->setImagePath('')->save();                            
                        }                         
                        break;
                }
                
            }
        }//eof foreach()
        // end original code m1510 parent::saveOptions();        
        
        
        $options = $this->getOptions();
        

        if (isset($post['customoptions']['groups']) && $productId) {
            $postGourps = $post['customoptions']['groups'];
            $groupModel = Mage::getSingleton('customoptions/group');
            $groups = $relation->getResource()->getGroupIds($productId, true);
            if (isset($groups) && is_array($groups)) {
                foreach ($groups as $id) {
                    if (!in_array($id, $postGourps)) {
                        $this->removeProductOptions($id, $productId);
                        $relation->getResource()->deleteGroupProduct($id, $productId);
                    } else {
                        $relationOptionIds = $relation->getResource()->getOptionIds($id, $productId);
                        if (isset($relationOptionIds) && is_array($relationOptionIds)) {
                            foreach ($relationOptionIds as $value) {
                                $check = Mage::getModel('catalog/product_option')->load($value)->getData();
                                if (empty($check)) {
                                    $relation->getResource()->deleteGroupProduct($id, $productId);
                                    break;
                                }
                            }
                        }
                        $key = array_search($id, $postGourps);
                        unset($postGourps[$key]);
                    }
                }
            }
            if (isset($postGourps) && is_array($postGourps)) {
                foreach ($postGourps as $groupId) {
                    if (!empty($groupId)) {
                        $groupData = $groupModel->load($groupId);
                        $optionsHash = unserialize($groupData->getData('hash_options'));

                        $optionIds = array();
                        $optionIds = $this->saveProductOptions($optionsHash, array($productId), $groupData, false, 'product');
                    }
                }
            }
            $relation->getResource()->changeHasOptionsKey($productId);
        }

        return $this;
    }       
    
    protected function _afterSave()
    {
        //parent::_afterSave();
        
        $optionId = $this->getData('option_id');
        $defaultArray = $this->getData('default') ? $this->getData('default') : array();
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $storeId = Mage::app()->getStore()->getId();        
        if (is_array($this->getData('values'))) {
            $values=array();
            foreach ($this->getData('values') as $key => $value) {
                if (isset($value['option_type_id'])) {    
                    $optionValue = array(
                        'option_id' => $optionId,
                        'sku' => $value['sku'],
                        'sort_order' => $value['sort_order'],
                        'customoptions_qty' => isset($value['customoptions_qty']) ? $value['customoptions_qty'] : '',
                        'default' => array_search($key, $defaultArray) !== false ? 1 : 0
                    );                                

                    $optionTypePrice = array(                    
                        'store_id' => $storeId,
                        'price' => $value['price'],
                        'price_type' => $value['price_type']
                    );                                                         

                    $optionTypeTitle = array(
                        'option_type_id' => $optionTypeId,
                        'store_id' => $storeId,
                        'title' => $value['title']
                    );                                                
                                                                
                    if ($value['option_type_id']>0) {                                                            
                        $optionTypeId = $value['option_type_id'];                    
                        if ($value['is_delete']=='1') {
                            $connection->delete($tablePrefix . 'catalog_product_option_type_value', 'option_type_id = ' . $optionTypeId);
                            $connection->delete($tablePrefix . 'catalog_product_option_type_price', 'option_type_id = ' . $optionTypeId);
                            $connection->delete($tablePrefix . 'catalog_product_option_type_title', 'option_type_id = ' . $optionTypeId);
                            Mage::helper('customoptions')->deleteValueFile(null, $optionId, $optionTypeId);
                        } else {
                            $connection->update($tablePrefix . 'catalog_product_option_type_value', $optionValue, 'option_type_id = ' . $optionTypeId);                                        
                            $optionTypePrice['option_type_id'] = $optionTypeId;
                            $connection->update($tablePrefix . 'catalog_product_option_type_price', $optionTypePrice, 'option_type_id = ' . $optionTypeId);
                            $optionTypeTitle['option_type_id'] = $optionTypeId;
                            $connection->update($tablePrefix . 'catalog_product_option_type_title', $optionTypeTitle, 'option_type_id = ' . $optionTypeId);
                        }    
                    } else {                    
                        $connection->insert($tablePrefix . 'catalog_product_option_type_value', $optionValue);                
                        $optionTypeId = $connection->lastInsertId($tablePrefix . 'catalog_product_option_type_value');
                        $optionTypePrice['option_type_id'] = $optionTypeId;
                        $connection->insert($tablePrefix . 'catalog_product_option_type_price', $optionTypePrice);
                        $optionTypeTitle['option_type_id'] = $optionTypeId;
                        $connection->insert($tablePrefix . 'catalog_product_option_type_title', $optionTypeTitle);                        
                    }

                    if ($optionTypeId>0) {
                        $id = $this->getData('id');                    
                        if ($this->_uploadImage('file_'.$id.'-'.$key, $optionId, $optionTypeId)) {                        
                            $connection->update($tablePrefix . 'catalog_product_option_type_value', array('image_path' => 'options' . DS . $optionId . DS . $optionTypeId . DS), 'option_type_id = ' . $optionTypeId);
                        }
                    }
                    unset($value['option_type_id']);
                }    
                
                $values[$key] = $value;
                
            }            
            $this->setData('values', $values);            
        
            
        } elseif ($this->getGroupByType($this->getType()) == self::OPTION_GROUP_SELECT) {
            Mage::throwException(Mage::helper('catalog')->__('Select type options required values rows.'));
        }
        
        $this->cleanModelCache();
        Mage::dispatchEvent('model_save_after', array('object'=>$this));
        Mage::dispatchEvent($this->_eventPrefix.'_save_after', $this->_getEventData());
        return $this;        
    }
    
    
    
    public function getProductOptionCollection(Mage_Catalog_Model_Product $product) {
        $collection = $this->getCollection()
                ->addFieldToFilter('product_id', $product->getId())
                ->addFieldToFilter('customoptions_status', array('neq' => MageWorx_CustomOptions_Helper_Data::STATUS_HIDDEN))
                ->addTitleToResult($product->getStoreId())
                ->addPriceToResult($product->getStoreId())
                ->addDescriptionToResult($product->getStoreId())
                ->setOrder('sort_order', 'asc')
                ->setOrder('title', 'asc')
                ->addValuesToResult($product->getStoreId());
        
        if (!Mage::app()->getStore()->isAdmin() && Mage::helper('customoptions')->isCustomerGroupsEnabled()) {
            $groupId = Mage::getSingleton('customer/session')->isLoggedIn() ? Mage::getSingleton('customer/session')->getCustomer()->getGroupId() : 0;
            foreach($collection as $key => $item) {
                $groups = explode(',', $item->getCustomerGroups());
                if (!in_array($groupId, $groups) ) {
                    $collection->removeItemByKey($key);
                }
            }
        }        
        return $collection;
    }

    public function getValuesAdvanced() {
        $values = $this->getResource()->getValuesAdvanced($this->getId());        
        return $values;
    }

    public function getOptionTitle() {
        $values = $this->getResource()->getOptionTitle($this->getId());
        return $values;
    }

    public function removeOptionFile($groupId, $optionId, $valueId = false, $isRemoveFolder = true) {
        $dir = Mage::helper('customoptions')->getCustomOptionsPath($groupId, $optionId, $valueId);
        $files = Mage::helper('customoptions')->getFiles($dir);
        if ($files) {
            foreach ($files as $value) {
                @unlink($value);
            }
            if ($isRemoveFolder === true) {
                $io = new Varien_Io_File();
                $io->rmdir($dir);
            }
        }
    }

    public function getOptionValue($valueId) {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();

        $select = $connection->select()->from($tablePrefix . 'catalog_product_option_type_value')->where('option_id = ' . $this->getId() . ' AND option_type_id = ' . $valueId);
        $row = $connection->fetchRow($select);
        return $row;
    }

}