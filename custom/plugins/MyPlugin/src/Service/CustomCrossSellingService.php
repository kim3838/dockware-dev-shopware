<?php

namespace MyPlugin\Service;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CustomCrossSellingService
{
    public function getCrossSellingProducts(
        $productId,
        SalesChannelContext $context
    ){
        return array(
            array(
                'name' => 'Cross Selling I',
                'price' => '10.00',
            ),
            array(
                'name' => 'Cross Selling II',
                'price' => '10.00',
            ),
            array(
                'name' => 'Cross Selling III',
                'price' => '10.00',
            ),
        );
    }
}