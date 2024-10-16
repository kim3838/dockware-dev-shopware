<?php declare(strict_types=1);

namespace MyPlugin\Subscriber;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Content\Product\ProductEvents;

class MySubscriber implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;
    private LoggerInterface $logger;

    public function __construct(
        SystemConfigService $systemConfigService,
        LoggerInterface $logger
    ){
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        // Return the events to listen to as array like this:  <event to listen to> => <method to execute>
        return [
            ProductEvents::PRODUCT_LOADED_EVENT => 'onProductsLoaded'
        ];
    }

    public function onProductsLoaded(EntityLoadedEvent $event)
    {
        $configCrossSellingGroupIndex = $this->systemConfigService->get('MyPlugin.config.minimalOffcanvasCrossSellingGroupIndex');

        $productCustomFieldCrossSellingGroupIndex = $event->getEntities()[0]->getVars()['customFields']['custom_minimal_offcanvas_cross_selling_group_index_'] ?? null;

        $minimalOffCanvasCrossSellingGroupIndex = empty($productCustomFieldCrossSellingGroupIndex)
            ? $configCrossSellingGroupIndex
            : $productCustomFieldCrossSellingGroupIndex;

        $minimalOffCanvasCrossSellingGroupIndex = empty($minimalOffCanvasCrossSellingGroupIndex) ? 1 : $minimalOffCanvasCrossSellingGroupIndex;

        $this->logger->debug('Config Cross Selling Group Index: ' . $configCrossSellingGroupIndex);
        $this->logger->debug('Product Custom Field Cross Selling Group Index: ' . $productCustomFieldCrossSellingGroupIndex);
        $this->logger->debug('Minimal Off-canvas Cross Selling Group Index: ' . $minimalOffCanvasCrossSellingGroupIndex);
    }
}
