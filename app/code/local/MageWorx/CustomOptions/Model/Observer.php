<?php

class MageWorx_CustomOptions_Model_Observer {

    public function addOrderQty($observer) {
        $item = $observer->getEvent()->getOrder();
        $orderItems = Mage::getModel('sales/order_item')
                ->getCollection()
                ->addFieldToFilter('order_id', $item->getId());

        foreach ($orderItems as $orderItem) {
            $optionIdsIterator = Mage::getModel('sales/quote_item_option')
                    ->getCollection()
                    ->addFieldToFilter('item_id', $orderItem->getQuoteItemId())
                    ->addFieldToFilter('code', 'option_ids')
                    ->getIterator();
            $optionIds = current($optionIdsIterator);
            if ($optionIds) {
                $optionIds = $optionIds->getValue();
                $optionIds = explode(',', $optionIds);

                $quoteItem = Mage::getModel('sales/quote_item')
                        ->load($orderItem->getQuoteItemId());

                foreach ($optionIds as $optionId) {
                    $typeIdIterator = Mage::getModel('sales/quote_item_option')
                            ->getCollection()
                            ->addFieldToFilter('item_id', $orderItem->getQuoteItemId())
                            ->addFieldToFilter('code', 'option_' . $optionId)
                            ->getIterator();
                    $typeId = current($typeIdIterator);
                    $typeId = $typeId->getValue();

                    $typeValue = Mage::getModel('catalog/product_option_value')
                            ->load($typeId);
                    if ($typeValue->getCustomoptionsQty() != '' && is_numeric($typeValue->getCustomoptionsQty()) && $typeValue->getCustomoptionsQty() > 0) {
                        $qty = $typeValue->getCustomoptionsQty(); // - intval($quoteItem->getQty());
                        if ($qty < 0)
                            $qty = 0;
                        $title = Mage::getResourceSingleton('customoptions/product_option')
                                ->getTitle($typeId, 0);
                        $typeValue->setCustomoptionsQty($qty);
                        $typeValue->save();
                        Mage::getResourceSingleton('customoptions/product_option')
                                ->setTitle($typeId, 0, $title);
                    }
                }
            }
        }
        return $this;
    }

    public function cancelOrderItem($observer) {
        if (!Mage::helper('customoptions')->isInventoryEnabled()) {
            return $this;
        }

        $item = $observer->getEvent()->getItem();

        $children = $item->getChildrenItems();
        $qty = $item->getQtyOrdered() - max($item->getQtyShipped(), $item->getQtyInvoiced()) - $item->getQtyCanceled();


        $iterator = Mage::getModel('sales/quote_item_option')
                ->getCollection()
                ->addFieldToFilter('item_id', $item->getQuoteItemId())
                ->addFieldToFilter('code', 'option_ids')
                ->getIterator();
        $optionIds = current($iterator);
        if ($optionIds) {
            $optionIds = $optionIds->getValue();
            $optionIds = explode(',', $optionIds);

            $quoteItem = Mage::getModel('sales/quote_item')
                    ->load($item->getQuoteItemId());

            foreach ($optionIds as $optionId) {
                $iterator = Mage::getModel('sales/quote_item_option')
                        ->getCollection()
                        ->addFieldToFilter('item_id', $item->getQuoteItemId())
                        ->addFieldToFilter('code', 'option_' . $optionId)
                        ->getIterator();
                $typeId = current($iterator);
                $typeId = $typeId->getValue();

                $typeValue = Mage::getModel('catalog/product_option_value')
                        ->load($typeId);

                if ($typeValue->getCustomoptionsQty() != '' && is_numeric($typeValue->getCustomoptionsQty())) {
                    $qty = intval($item->getQtyOrdered() - max($item->getQtyShipped(), $item->getQtyInvoiced()) - $item->getQtyCanceled());
                    if ($qty < 0)
                        $qty = 0;

                    if ($typeValue->getSku() && $typeValue->getSku() != '') {
                        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $typeValue->getSku());
                        if ($product && $product->getId() > 0) {
                            $item = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
                            if ($item->checkQty($qty)) {
                                $item->addQty($qty);
                                $item->save();
                            }
                        }
                    }
                    
                    $title = Mage::getResourceSingleton('customoptions/product_option')
                            ->getTitle($typeId, 0);
                    $typeValue->setCustomoptionsQty($typeValue->getCustomoptionsQty() + $qty);
                    $typeValue->save();
                    Mage::getResourceSingleton('customoptions/product_option')
                            ->setTitle($typeId, 0, $title);
                }
            }
        }
        return $this;
    }

    public function creditMemoRefund($observer) {
        if (!Mage::helper('customoptions')->isInventoryEnabled()) {
            return $this;
        }
              
        $creditMemo = $observer->getEvent()->getCreditmemo();
        
        foreach ($creditMemo->getItemsCollection() as $creditMemoItem) {     
			$item = $creditMemoItem->getOrderItem();
               
#			$qty = $item->getQtyOrdered() - max($item->getQtyShipped(), $item->getQtyInvoiced()) - 
#				$item->getQtyCanceled();
	
#			$qty = $item->getQtyRefunded();
	        $qty = (int)$creditMemoItem->getBackToStock();
	
			$iterator = Mage::getModel('sales/quote_item_option')
					->getCollection()
					->addFieldToFilter('item_id', $item->getQuoteItemId())
					->addFieldToFilter('code', 'option_ids')
					->getIterator();
			$optionIds = current($iterator);
			if ($optionIds) {
				$optionIds = $optionIds->getValue();
				$optionIds = explode(',', $optionIds);
	
				$quoteItem = Mage::getModel('sales/quote_item')
						->load($item->getQuoteItemId());
						
	
				foreach ($optionIds as $optionId) {
					$iterator = Mage::getModel('sales/quote_item_option')
							->getCollection()
							->addFieldToFilter('item_id', $item->getQuoteItemId())
							->addFieldToFilter('code', 'option_' . $optionId)
							->getIterator();
					$typeId = current($iterator);
					$typeId = $typeId->getValue();
	
					$typeValue = Mage::getModel('catalog/product_option_value')
							->load($typeId);
					if ($typeValue->getCustomoptionsQty() != '' && is_numeric($typeValue->getCustomoptionsQty())) {
						if ($qty < 0)
							$qty = 0;
							
						$product = Mage::getModel('catalog/product')->load($item->getProductId());
						if ($product && $product->getId() > 0) {
							$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
							if ($stockItem->checkQty($qty)) {
								$stockItem->addQty($qty);
								$stockItem->save();
							}
						}
													
						$title = Mage::getResourceSingleton('customoptions/product_option')
								->getTitle($typeId, 0);
						$typeValue->setCustomoptionsQty($typeValue->getCustomoptionsQty() + $qty);
						$typeValue->save();
						Mage::getResourceSingleton('customoptions/product_option')
								->setTitle($typeId, 0, $title);
					}
				}
			}
		}
        return $this;
    }

    public function createOrderItem($observer) {
        $item = $observer->getEvent()->getItem();

        $children = $item->getChildrenItems();

        if ($item->getId() && empty($children)) {
            $qty = $item->getQtyOrdered() - max($item->getQtyShipped(), $item->getQtyInvoiced()) - $item->getQtyCanceled();

            $optionsIdsIterator = Mage::getModel('sales/quote_item_option')
                    ->getCollection()
                    ->addFieldToFilter('item_id', $item->getQuoteItemId())
                    ->addFieldToFilter('code', 'option_ids')
                    ->getIterator();
            $optionIds = current($optionsIdsIterator);
            if ($optionIds) {
                $optionIds = $optionIds->getValue();
                $optionIds = explode(',', $optionIds);

                $quoteItem = Mage::getModel('sales/quote_item')
                        ->load($item->getQuoteItemId());

                foreach ($optionIds as $optionId) {
                    $typeIdIterator = Mage::getModel('sales/quote_item_option')
                            ->getCollection()
                            ->addFieldToFilter('item_id', $item->getQuoteItemId())
                            ->addFieldToFilter('code', 'option_' . $optionId)
                            ->getIterator();
                    $typeId = current($typeIdIterator);
                    $typeId = $typeId->getValue();

                    $typeValue = Mage::getModel('catalog/product_option_value')
                            ->load($typeId);
                    if ($typeValue->getCustomoptionsQty() != '' && is_numeric($typeValue->getCustomoptionsQty()) && $typeValue->getCustomoptionsQty() > 0) {
                        $qty = $typeValue->getCustomoptionsQty() - intval($quoteItem->getQty());
                        if ($qty < 0)
                            $qty = 0;
                        $title = Mage::getResourceSingleton('customoptions/product_option')
                                ->getTitle($typeId, 0);
                        
                        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $typeValue->getSku());
                        if (isset($product) && $product && $product->getId() > 0) {
                            $item = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
                            if ($item->checkQty($qty)) {
                                $item->subtractQty($quoteItem->getQty());
                                $item->save();
                            }
                        }

                        $typeValue->setCustomoptionsQty($qty);
                        $typeValue->save();
                        Mage::getResourceSingleton('customoptions/product_option')
                                ->setTitle($typeId, 0, $title);
                    }
                }
            }
        }

        return $this;
    }

}