<?php

namespace MyPlugin\Service;

use Shopware\Core\Content\Product\Aggregate\ProductCrossSellingAssignedProducts\ProductCrossSellingAssignedProductsEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class CustomCrossSellingService
{
    private $crossSellingRepository;
    private $productRepository;
    private SystemConfigService $systemConfigService;

    public function __construct(EntityRepository $productRepository, EntityRepository $crossSellingRepository, SystemConfigService $systemConfigService,)
    {
        $this->productRepository = $productRepository;
        $this->crossSellingRepository = $crossSellingRepository;
        $this->systemConfigService = $systemConfigService;
    }

    public function getOffCanvasCrossSellingGroupIndex(?ProductEntity $productEntity)
    {
        if(!$productEntity){
            return null;
        }

        $configCrossSellingGroupIndex = $this->systemConfigService->get('MyPlugin.config.minimalOffcanvasCrossSellingGroupIndex');

        $productCustomFieldCrossSellingGroupIndex = $productEntity->getVars()['customFields']['custom_minimal_offcanvas_cross_selling_group_index_'] ?? null;

        $minimalOffCanvasCrossSellingGroupIndex = empty($productCustomFieldCrossSellingGroupIndex)
            ? $configCrossSellingGroupIndex
            : $productCustomFieldCrossSellingGroupIndex;

        $minimalOffCanvasCrossSellingGroupIndex = empty($minimalOffCanvasCrossSellingGroupIndex) ? 1 : $minimalOffCanvasCrossSellingGroupIndex;

        return $minimalOffCanvasCrossSellingGroupIndex;
    }

    public function getCrossSellingProducts(?ProductEntity $productEntity, SalesChannelContext $context)
    {
        if(!$productEntity){
            return [];
        }

        $minimalOffCanvasCrossSellingGroupIndex = $this->getOffCanvasCrossSellingGroupIndex($productEntity);

        $productCrossSellingCriteria = new Criteria();
        $productCrossSellingCriteria->setTitle('product-cross-selling-route');
        $productCrossSellingCriteria
            ->addAssociations([
                'assignedProducts.product.media',
                'assignedProducts.product.options',
            ])
            ->addFilter(new EqualsFilter('product.id', $productEntity->id))
            ->addFilter(new EqualsFilter('active', 1))
            ->addFilter(new EqualsFilter('position', $minimalOffCanvasCrossSellingGroupIndex));

        $searchResult = $this->crossSellingRepository->search($productCrossSellingCriteria, Context::createDefaultContext());

        $crossSelling = $searchResult->getEntities()->first();
        $crossSellingProducts = [];

        if($crossSelling){
            foreach($crossSelling->getAssignedProducts() as $assignedProduct){

                if ($assignedProduct instanceof ProductCrossSellingAssignedProductsEntity) {

                    $product = $assignedProduct->getProduct();
                    $crossSellingProducts[] = [
                        'is_variant' => !empty($product->parentId),
                        'entity' => $product,
                        'name' => $product?->getName(),
                        'price' => $product?->getPrice()?->first()?->getGross(),
                        'media' => $product?->getMedia()?->first()?->getMedia(),
                    ];
                }
            }
        }

        return [
            'entity' => $crossSelling,
            'products' => $crossSellingProducts
        ];
    }
}