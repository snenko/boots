<?php

class MageWorx_CustomOptions_Model_CatalogInventory_Stock extends Mage_CatalogInventory_Model_Stock {

    public function registerItemSale(Varien_Object $item) {
        if ($productId = $item->getProductId()) {
            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
            if (Mage::helper('catalogInventory')->isQty($stockItem->getTypeId())) {
                if ($item->getStoreId()) {
                    $stockItem->setStoreId($item->getStoreId());
                }
                if ($stockItem->checkQty($item->getQtyOrdered()) || Mage::app()->getStore()->isAdmin()) {

                    $product = Mage::getModel('catalog/product')->load($productId);
                    $productOptions = Mage::getModel('catalog/product_option')->getProductOptionCollection($product);

                    $stockItem->subtractQty($item->getQtyOrdered());
                    $stockItem->save();
                }
            }
        } else {
            Mage::throwException(Mage::helper('cataloginventory')->__('Can not specify product identifier for order item'));
        }
        return $this;
    }

    public function registerProductsSale($items) {

        $qtys = $this->_prepareProductQtys($items);
        $item = Mage::getModel('cataloginventory/stock_item');
        $this->_getResource()->beginTransaction();
        $stockInfo = $this->_getResource()->getProductsStock($this, array_keys($qtys), true);
        $fullSaveItems = array();
        foreach ($stockInfo as $itemInfo) {
            $item->setData($itemInfo);
            if (!$item->checkQty($qtys[$item->getProductId()])) {
                $this->_getResource()->commit();
                Mage::throwException(Mage::helper('cataloginventory')->__('Not all products are available in the requested quantity'));
            }
            $item->subtractQty($qtys[$item->getProductId()]);
            if (!$item->verifyStock() || $item->verifyNotification()) {
                $fullSaveItems[] = clone $item;
            }
        }

//        if (Mage::helper('customoptions')->isInventoryEnabled()) {
//            $quoteItems = Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection();
//            foreach ($quoteItems as $quoteItem) {
//                $options = $quoteItem->getOptions();
//                foreach ($options as $option) {
//                    if (intval($option->getValue()) > 0) {
//                        continue;
//                    }
//                    $value = unserialize($option->getValue());
//                    foreach ($value['options'] as $optionId => $valueId) {
//                        $productOption = Mage::getModel('catalog/product_option')->load($optionId);
//                        $optionValue = $productOption->getOptionValue($valueId);
//                        if (isset($optionValue['sku']) && $optionValue['sku'] != '') {
//                            $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $optionValue['sku']);
//                            if ($product->getId() > 0) {
//                                $item = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
//
//                                if ($item->checkQty($qtys[$quoteItem->getProductId()])) {
//                                    //$item->subtractQty($qtys[$quoteItem->getProductId()]);
//                                    $item->save();
//                                }
//                            }
//                        }
//                    }
//                }
//            }
//        }

        $this->_getResource()->correctItemsQty($this, $qtys, '-');
        $this->_getResource()->commit();

        return $fullSaveItems;
    }

    public function backItemQty($productId, $qty) {
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
        if ($stockItem->getId() && Mage::helper('catalogInventory')->isQty($stockItem->getTypeId())) {
            $stockItem->addQty($qty);
            if ($stockItem->getCanBackInStock() && $stockItem->getQty() > $stockItem->getMinQty()) {
                $stockItem->setIsInStock(true)
                        ->setStockStatusChangedAutomaticallyFlag(true);
            }
            $stockItem->save();
        }
        return $this;
    }

}