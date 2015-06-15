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
 * @package    MageWorx_Adminhtml
 * @copyright  Copyright (c) 2009 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */

/**
 * MageWorx Adminhtml extension
 *
 * @category   MageWorx
 * @package    MageWorx_Adminhtml
 * @author     MageWorx Dev Team <dev@mageworx.com>
 */
class MageWorx_Adminhtml_Block_Customoptions_Options_Edit_Tab_Options_Option extends MageWorx_Adminhtml_Block_Customoptions_Adminhtml_Catalog_Product_Edit_Tab_Options_Option {

    public function __construct() {
        parent::__construct();
    }

    protected function getStoreId() {
        if (Mage::app()->getStore()->isAdmin()) {
            return Mage::registry('store_id');
        } else {
            return Mage::app()->getStore()->getId();
        }
    }

    public function getOptionValues() {        
        
        $data = array();
        $optionsArr = '';
        $session = Mage::getSingleton('adminhtml/session');
        if ($data = $session->getData('customoptions_data')) {
            if (isset($data['general']['hash_options'])) {
                $optionsArr = $data['general']['hash_options'];
            }
        } elseif (Mage::registry('customoptions_data')) {
            $data = Mage::registry('customoptions_data')->getData();
            if (isset($data['hash_options'])) {
                $optionsArr = $data['hash_options'];
            }
        }

        $groupId = (int) $this->getRequest()->getParam('group_id');

        if ($optionsArr) {
            $optionsArr = unserialize($optionsArr);
        }
        $product = Mage::getSingleton('catalog/product_option');
        if (!$this->_values && $optionsArr) {
            $values = array();
            $sortOrder = array();
            $scope = (int) Mage::app()->getStore()->getConfig(Mage_Core_Model_Store::XML_PATH_PRICE_SCOPE);
            $optionItemCount = count($optionsArr);
            foreach ($optionsArr as $option) {
                $option = new Varien_Object($option);
                $value = array();                
                if ($option->getIsDelete() != '1') {
                    $value['id'] = $option->getOptionId();
                    $value['item_count'] = $optionItemCount;
                    $value['option_id'] = $option->getOptionId();
                    $value['title'] = $this->htmlEscape($option->getTitle());
                    $value['type'] = $option->getType();
                    $value['is_require'] = $option->getIsRequire();                    
                    $value['customoptions_is_onetime'] = $option->getCustomoptionsIsOnetime();
                    $value['description'] = $this->htmlEscape($option->getDescription());
                    if (Mage::helper('customoptions')->isCustomerGroupsEnabled() && $option->getCustomerGroups() != null) {
                        $value['customer_groups'] = implode(',', $option->getCustomerGroups());
                    }
                    
                    $value['sort_order'] = $this->_getSortOrder($option);
                    $sortOrder[$option->getOptionId()] = $this->_getSortOrder($option);

                    if ($this->getStoreId() != '0') {
                        $value['checkboxScopeTitle'] = $this->getCheckboxScopeHtml($option->getOptionId(), 'title', is_null($option->getStoreTitle()));
                        $value['scopeTitleDisabled'] = is_null($option->getStoreTitle()) ? 'disabled' : null;
                        $value['checkboxScopeDescription'] = $this->getCheckboxScopeHtml($option->getOptionId(), 'description', is_null($option->getStoreDescription()));
                        $value['scopeDescriptionDisabled'] = is_null($option->getStoreDescription()) ? 'disabled' : null;
                    }                                        

                    if ($product->getGroupByType($option->getType()) == Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT) {
                        $valueSortOrder = array();
                        $countValues = count($option->getValues());
                        foreach ($option->getValues() as $key => $_value) {
                            $_value = new Varien_Object($_value);
                            $_value->setOptionTypeId($key);

                            if ($_value->getIsDelete() != '1') {

                                $defaultArray = $option->getDefault() !== null ? $option->getDefault() : array();

                                $value['optionValues'][$key] = array(
                                    'item_count' => $countValues,
                                    'option_id' => $option->getOptionId(),
                                    'option_type_id' => $_value->getOptionTypeId(),
                                    'title' => $this->htmlEscape($_value->getTitle()),
                                    'price' => $this->getPriceValue($_value->getPrice(), $_value->getPriceType()),
                                    'price_type' => $_value->getPriceType(),
                                    'sku' => $this->htmlEscape($_value->getSku()),
                                    'sort_order' => $this->_getSortOrder($_value),
                                    'customoptions_qty' => $_value->getCustomoptionsQty(),                                    
                                    'checked' => array_search($_value->getOptionTypeId(), $defaultArray) !== false ? 'checked' : '',
                                    'default_type' => $option->getType() == 'checkbox' ? 'checkbox' : 'radio'
                                );
                                $valueSortOrder[$key] = $this->_getSortOrder($_value);
                                                                
                                $value['optionValues'][$key]['image_button_label'] = Mage::helper('customoptions')->__('Add Image');

                                if (Mage::helper('customoptions')->isCustomOptionsFile($groupId, $option->getOptionId(), $_value->getOptionTypeId())) {
                                    $impOption = array(
                                        'label' => Mage::helper('customoptions')->__('Delete Image'),
                                        'url' => $this->getUrl('mageworx/customoptions_options/getImage/', array(
                                            'group_id' => $groupId,
                                            'option_id' => $option->getOptionId(),
                                            'value_id' => $_value->getOptionTypeId(),
                                        )),
                                        'value_id' => $_value->getOptionTypeId(),
                                        'id' => $option->getOptionId()
                                    );
                                    $value['optionValues'][$key]['image'] = Mage::helper('customoptions')->getValueImgView(new Varien_Object($impOption));
                                    $value['optionValues'][$key]['image_button_label'] = Mage::helper('customoptions')->__('Change Image');
                                }

                                if ($this->getStoreId() != '0') {
                                    $value['optionValues'][$key]['checkboxScopeTitle'] = $this->getCheckboxScopeHtml($option->getOptionId(), 'title', is_null($_value->getStoreTitle()), $_value->getOptionTypeId());
                                    $value['optionValues'][$key]['scopeTitleDisabled'] = is_null($_value->getStoreTitle()) ? 'disabled' : null;

                                    if ($scope == Mage_Core_Model_Store::PRICE_SCOPE_WEBSITE) {
                                        $value['optionValues'][$key]['checkboxScopePrice'] = $this->getCheckboxScopeHtml($option->getOptionId(), 'price', is_null($_value->getstorePrice()), $_value->getOptionTypeId());
                                        $value['optionValues'][$key]['scopePriceDisabled'] = is_null($_value->getStorePrice()) ? 'disabled' : null;
                                    }
                                }
                            }
                        }
                        $value['optionValues'] = $this->_setSortOrder($value['optionValues'], $valueSortOrder);
                    } else {
                        $value['price'] = $this->getPriceValue($option->getPrice(), $option->getPriceType());
                        $value['price_type'] = $option->getPriceType();
                        $value['sku'] = $this->htmlEscape($option->getSku());
                        $value['max_characters'] = $option->getMaxCharacters();
                        $value['file_extension'] = $option->getFileExtension();
                        $value['image_size_x'] = $option->getImageSizeX();
                        $value['image_size_y'] = $option->getImageSizeY();
                        $value['image_button_label'] = Mage::helper('customoptions')->__('Add Image');
                        
                        if (file_exists(Mage::helper('customoptions')->getCustomOptionsPath($groupId, $option->getOptionId()))) {
                            $impOption = array(
                                'label' => Mage::helper('customoptions')->__('Delete Image'),
                                'url' => $this->getUrl('mageworx/customoptions_options/getImage/', array('group_id' => $groupId, 'option_id' => $option->getOptionId())),
                                'id' => $option->getOptionId()
                            );
                            $value['image'] = Mage::helper('customoptions')->getValueImgView(new Varien_Object($impOption));                            
                            $value['image_button_label'] = Mage::helper('customoptions')->__('Change Image');
                        }


                        if ($this->getStoreId() != '0' && $scope == Mage_Core_Model_Store::PRICE_SCOPE_WEBSITE) {
                            $value['checkboxScopePrice'] = $this->getCheckboxScopeHtml($option->getOptionId(), 'price', is_null($option->getStorePrice()));
                            $value['scopePriceDisabled'] = is_null($option->getStorePrice()) ? 'disabled' : null;
                        }
                    }
                    $values[] = new Varien_Object($value);
                }
            }
            $values = $this->_setSortOrder($values, $sortOrder, false);
            $this->_values = $values;
        }
        return $this->_values ? $this->_values : array();
    }

    private function _getSortOrder(Varien_Object $obj) {
        $sortOrder = $obj->getSortOrder();
        return empty($sortOrder) ? 0 : $sortOrder;
    }

    private function _setSortOrder($data, $sortKeys, $isValue = true) {
        if (is_array($data) && $data && is_array($sortKeys) && $sortKeys) {
            $result = array();
            if ($isValue === true) {
//                sort($sortKeys);
                uksort($sortKeys, array($this, "sortKeys"));
                foreach ($sortKeys as $key => $sort) {
                    $result[] = $data[$key];
                }
                $i = 1;
                foreach ($result as $key => $val) {
                    $result[$key]['option_type_id'] = $i;
                    $i++;
                }
            } else {
                uksort($sortKeys, array($this, "sortKeys"));
                foreach ($sortKeys as $key => $sort) {
                    foreach ($data as $objKey => $value) {
                        if ($value->getId() == $key) {
                            $result[] = $data[$objKey];
                        }
                    }
                }
            }
            return $result;
        } else {
            return $data;
        }
    }
    
    private function sortKeys($a, $b) {
        if ($a == $b) {
            return 0;
        }
        return $a < $b ? -1 : 1;
    }

}