<?php

namespace Soumbala\Core\Setup\Patch\Data;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Model\CategoryFactory;

class DeleteProductsCategories implements DataPatchInterface
{
    const NAME_OPTION = "categories";

    protected $_objectManager;
    protected $_registry;
    protected $_productCollectionFactory;
    protected $_productRepository;

    public function __construct(
        CollectionFactory $productCollectionFactory,
        CategoryFactory $categoryFactory,
        ProductRepositoryInterface $productRepository
    ) { 
        $this->productRepository = $productRepository;
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryFactory = $categoryFactory;
        
        $this->objectManager->get(\Magento\Framework\Registry::class)->register('isSecureArea', true);
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
       // $this->deleteAllProducts();
        $this->deleteAllCategories();
    }
    
    private function deleteAllCategories()
    {
        $newCategory = $this->categoryFactory->create();
        $collection = $newCategory->getCollection();
        $i=0;
        
        foreach ($collection as $category) {
            if ($category->getId() > 2) {
                $category->delete();
                $i++;
            }
        }
        return $i;
    }

    /**
     * 
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    private function deleteAllProducts()
    {
        $collection = $this->productCollectionFactory->create()->addAttributeToSelect('*')->load();
        $app_state = $this->objectManager->get(\Magento\Framework\App\State::class);
        $app_state->setAreaCode('frontend');
        $i=0;
        foreach ($collection as $product) {
            $this->productRepository->deleteById($product->getSku());
            $i++;
        }
        return $i;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}