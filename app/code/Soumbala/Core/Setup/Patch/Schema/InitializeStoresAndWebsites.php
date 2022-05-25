<?php

namespace Soumbala\Core\Setup\Patch\Schema;

use Magento\Catalog\Helper\DefaultCategory;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Catalog\Helper\DefaultCategoryFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\ResourceModel\Group;
use Magento\Store\Model\ResourceModel\Store;
use Magento\Store\Model\ResourceModel\Website;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\WebsiteFactory;


/**
 * Create stores and websites. Actually stores and websites are part of schema as
 * other modules schema relies on store and website presence.
 */
class InitializeStoresAndWebsites implements SchemaPatchInterface
{
    /**
     * @var SchemaSetupInterface
     */
    private $schemaSetup;

    /**
     * @var DefaultCategory
     */
    private $defaultCategory;

    /**
     * @var DefaultCategoryFactory
     */
    private $defaultCategoryFactory;

    /**
     * @var ManagerInterface
     */
    private $eventManager;
    /**
     * @var GroupFactory
     */
    private $groupFactory;
    /**
     * @var Group
     */
    private $groupResourceModel;
    /**
     * @var StoreFactory
     */
    private $storeFactory;
    /**
     * @var Store
     */
    private $storeResourceModel;
    /**
     * @var WebsiteFactory
     */
    private $websiteFactory;
    /**
     * @var Website
     */
    private $websiteResourceModel;

    /**
     * PatchInitial constructor.
     *
     * @param SchemaSetupInterface $schemaSetup
     * @param DefaultCategoryFactory $defaultCategoryFactory
     * @param Group $groupResourceModel
     * @param GroupFactory $groupFactory
     * @param ManagerInterface $eventManager
     * @param Store $storeResourceModel
     * @param StoreFactory $storeFactory
     * @param Website $websiteResourceModel
     * @param WebsiteFactory $websiteFactory
     */
    public function __construct(
        SchemaSetupInterface $schemaSetup,
        DefaultCategoryFactory $defaultCategoryFactory,
        Group $groupResourceModel,
        GroupFactory $groupFactory,
        ManagerInterface $eventManager,
        Store $storeResourceModel,
        StoreFactory $storeFactory,
        Website $websiteResourceModel,
        WebsiteFactory $websiteFactory
    ) {
        $this->schemaSetup = $schemaSetup;
        $this->defaultCategoryFactory = $defaultCategoryFactory;
        $this->eventManager = $eventManager;
        $this->groupFactory = $groupFactory;
        $this->groupResourceModel = $groupResourceModel;
        $this->storeFactory = $storeFactory;
        $this->storeResourceModel = $storeResourceModel;
        $this->websiteFactory = $websiteFactory;
        $this->websiteResourceModel = $websiteResourceModel;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->schemaSetup->startSetup();
        $data = [
                'website_code' => 'soumbala',
                'website_name' => 'Soumbala',
                'group_name' => 'Soumbala Store',
                'group_code' => 'soumbala_store',
                'store_code' => 'soumbala_store_view',
                'store_name' => 'Soumbala Store View',
                'is_active' => '1'
        ];
        
        /** @var  \Magento\Store\Model\Store $store */
        $store = $this->storeFactory->create();
        $store->load($data['store_code']);

        if (!$store->getId()) {
            /** @var \Magento\Store\Model\Website $website */
            $website = $this->websiteFactory->create();
            $website->load($data['website_code']);
            
            if (!$website->getId()) {
                $website->setCode($data['website_code']);
                $website->setName($data['website_name']);
                $this->websiteResourceModel->save($website);
            }
    
            /** @var \Magento\Store\Model\Group $group */
            $group = $this->groupFactory->create();
            $group->setWebsiteId($website->getWebsiteId());
            $group->setName($data['group_name']);
            $group->setCode($data['group_code']);
            $group->setRootCategoryId($this->getDefaultCategory()->getId());
            $this->groupResourceModel->save($group);
            
            $store->setCode($data['store_code']);
            $store->setName($data['store_name']);
            $store->setWebsite($website);
            $store->setGroupId($group->getId());
            $store->setData('is_active', $data['is_active']);
            $this->storeResourceModel->save($store);
        }
        $this->eventManager->dispatch('store_add', ['store' => $store]);
            
        $this->schemaSetup->endSetup();
    }

    /**
     * Get default category.
     *
     * @return DefaultCategory
     */
    private function getDefaultCategory()
    {
        if ($this->defaultCategory === null) {
            $this->defaultCategory = $this->defaultCategoryFactory->create();
        }
        return $this->defaultCategory;
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
