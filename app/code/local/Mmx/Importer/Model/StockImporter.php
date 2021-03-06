<?php

class Mmx_Importer_Model_StockImporter {

    /**
     *
     * @var Mmx_Importer_Helper_Xml
     */
    protected $helper;

    /**
     *
     * @var int
     */
    protected $website_id;
    
    /**
     *
     * @var int
     */
    protected $category_id;

    /**
     * 
     * @return Mmx_Importer_Helper_Xml
     */
    public function getHelper() {
        return $this->helper;
    }

    /**
     * 
     * @param Mmx_Importer_Helper_Xml $helper
     * @return $this
     */
    public function setHelper(Mmx_Importer_Helper_Xml $helper) {
        $this->helper = $helper;
        return $this;
    }    
    
    /**
     * 
     * @return int
     */
    public function getWebsiteId() {
        return $this->website_id;
    }

    /**
     * 
     * @param int $website_id
     * @return $this
     */
    public function setWebsiteId($website_id) {
        $this->website_id = $website_id;
        return $this;
    }

    /**
     * 
     * @return int
     */
    public function getCategoryId() {
        return $this->category_id;
    }

    /**
     * 
     * @param int $category_id
     * @return $this
     */
    public function setCategoryId($category_id) {
        $this->category_id = $category_id;
        return $this;
    }

    public function update() {
        
        if ($nodes = $this->helper->getNodes()) {
            foreach ($nodes as $node) {

                $sku = trim((string) $node->attributes()->product);
                $name = trim((string) $node->attributes()->description);
                $description = trim((string) $node->attributes()->description);
                $short_description = trim((string) $node->attributes()->description);
                $website_id = $this->website_id;
                $category_id = $this->category_id;
                $qty = $node->attributes()->Free_Stock;

                /* @var $product Mage_Catalog_Model_Product */
                $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
                if (!is_object($product) || !$product->getId()) {

                    // Insert
                    $this->log("Creating new product: {$sku}");
                    $product = $this->createProduct($sku, $category_id, $website_id, $name, $description, $short_description, $qty);
                }
                else {

                    // Update
                    $this->log("Found existing product: {$sku}");
                    if ($this->stockChanged($product, $qty)) {
                        $this->log("Updating stock: {$sku}");                        
                        $this->updateStock($product, $qty);
                    }
                }

            }

            $this->log('Finished processing ' . $this->helper->getXmlFilename());
        }
         
    }
    
    /**
     * 
     * @param Mage_Catalog_Model_Product $product
     * @param int $qty
     * @return Mage_Core_Model_Abstract
     */
    public function updateStock(Mage_Catalog_Model_Product $product, $qty) {

        /* @var $stockItem Mage_CatalogInventory_Model_Stock_Item */
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());

        if ($qty > 0) {
            $is_in_stock = 1;
        } else {
            $is_in_stock = 0;
        }

        $stockItem->setIsInStock($is_in_stock)
                ->setQty($qty)
                ->save();

        return $product;
    }
    
    /**
     * 
     * @param Mage_Catalog_Model_Product $product
     * @return Mage_Core_Model_Abstract
     */
    public function getStockQty(Mage_Catalog_Model_Product $product) {

        /* @var $stockItem Mage_CatalogInventory_Model_Stock_Item */
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());

        return $stockItem->getQty();
    }    

    /**
     * 
     * @param Mage_Catalog_Model_Product $product
     * @param type $qty
     * @return boolean
     */
    public function stockChanged(Mage_Catalog_Model_Product $product, $qty) {

        /* @var $stockItem Mage_CatalogInventory_Model_Stock_Item */
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());
        
        if ($stockItem->getQty() != $qty) {
            return true;
        }
        else {
            return false;
        }
    }
    
    /**
     * 
     * @return Mage_Catalog_Model_Product
     */
    public function createProduct($sku, $category_id, $website_id, $name, $description, $short_description, $qty) {

        /* @var $product Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product');

        // Build the product
        $product->setSku($sku);
        $product->setAttributeSetId(4); // Default attribute set
        $product->setCategoryIds(array($category_id));
        $product->setTypeId('simple');
        $product->setName(trim($name));
        $product->setWebsiteIDs(array($website_id));
        $product->setDescription(trim($description));
        $product->setShortDescription(trim($short_description));
        $product->setPrice(0);
        $product->setWeight(0);
        $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
        $product->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $product->setTaxClassId(0);
        $product->setIsMassUpdate(true);
        
        // Stock data
        if ($qty > 0) {
            $is_in_stock = 1;
        } else {
            $is_in_stock = 0;
        }
        $product->setStockData(array(
            'is_in_stock' => $is_in_stock,
            'qty' => $qty
        ));

        // Done
        try {
            $product->save();
        } catch (Exception $ex) {
            Mage::logException($ex, Zend_Log::ERR, 'mmx_importer.log', true);
        }    

        return $product;
    }

    /**
     * 
     * @param string $message
     */
    public function log($message) {
        Mage::log($message, Zend_Log::INFO, 'mmx_importer.log', true);
    }
    
}
