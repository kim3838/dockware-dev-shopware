<?php declare(strict_types=1);

namespace MyPlugin\Storefront\Controller;

use MyPlugin\Service\CustomCrossSellingService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Checkout\Cart\LineItemFactoryRegistry;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Content\Product\Exception\ProductNotFoundException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Profiling\Profiler;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
#[Route(defaults: ['_routeScope' => ['storefront']])]
class MinimalOffCanvasCartLineItemController extends StoreFrontController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly LineItemFactoryRegistry $lineItemFactoryRegistry,
        private readonly CustomCrossSellingService $customCrossSellingService
    ){}

    #[Route(path: '/checkout/line-item/add', name: 'frontend.checkout.line-item.add', defaults: ['XmlHttpRequest' => true], methods: ['POST'])]
    public function addLineItems(Cart $cart, RequestDataBag $requestDataBag, Request $request, SalesChannelContext $context): Response
    {
        return Profiler::trace('cart::add-line-item', function () use ($cart, $requestDataBag, $request, $context) {
            /** @var RequestDataBag|null $lineItems */
            $lineItems = $requestDataBag->get('lineItems');
            if (!$lineItems) {
                throw RoutingException::missingRequestParameter('lineItems');
            }

            $count = 0;
            $lastAddedProductId = null;

            try {
                $items = [];
                /** @var RequestDataBag $lineItemData */
                foreach ($lineItems as $lineItemData) {
                    try {
                        $item = $this->lineItemFactoryRegistry->create($this->getLineItemArray($lineItemData, [
                            'quantity' => 1,
                            'stackable' => true,
                            'removable' => true,
                        ]), $context);

                        $count += $item->getQuantity();

                        $lastAddedProductId = $item->getId();

                        $items[] = $item;

                    } catch (CartException $e) {
                        if ($e->getErrorCode() === CartException::CART_INVALID_LINE_ITEM_QUANTITY_CODE) {
                            $this->addFlash(
                                self::DANGER,
                                $this->trans(
                                    'error.CHECKOUT__CART_INVALID_LINE_ITEM_QUANTITY',
                                    [
                                        '%quantity%' => $e->getParameter('quantity'),
                                    ]
                                )
                            );

                            return $this->createActionResponse($request);
                        }

                        throw $e;
                    }
                }

                $cart = $this->cartService->add($cart, $items, $context);

                if (!$this->traceErrors($cart)) {
                    $this->addFlash(self::SUCCESS, $this->trans('checkout.addToCartSuccess', ['%count%' => $count]));
                }
            } catch (ProductNotFoundException|RoutingException) {
                $this->addFlash(self::DANGER, $this->trans('error.addToCartError'));
            }

            $request->query->set('redirectParameters', json_encode([
                'action_response_from' => 'cart::add-line-item',
                'last_engaged_product_id' => $lastAddedProductId,
                'unique_item_add_count' => count($items)
            ]));

            return $this->createActionResponse($request);
        });
    }

    private function traceErrors(Cart $cart): bool
    {
        if ($cart->getErrors()->count() <= 0) {
            return false;
        }

        $this->addCartErrors($cart, fn (Error $error) => $error->isPersistent());

        return true;
    }

    private function getLineItemArray(RequestDataBag $lineItemData, ?array $defaultValues): array
    {
        if ($lineItemData->has('payload')) {
            $payload = $lineItemData->get('payload');

            if (mb_strlen($payload, '8bit') > (1024 * 256)) {
                throw RoutingException::invalidRequestParameter('payload');
            }

            $lineItemData->set('payload', json_decode($payload, true, 512, \JSON_THROW_ON_ERROR));
        }
        $lineItemArray = $lineItemData->all();

        if (isset($lineItemArray['quantity'])) {
            $lineItemArray['quantity'] = (int) $lineItemArray['quantity'];
        } elseif (isset($defaultValues['quantity'])) {
            $lineItemArray['quantity'] = $defaultValues['quantity'];
        }

        if (isset($lineItemArray['stackable'])) {
            $lineItemArray['stackable'] = (bool) $lineItemArray['stackable'];
        } elseif (isset($defaultValues['stackable'])) {
            $lineItemArray['stackable'] = $defaultValues['stackable'];
        }

        if (isset($lineItemArray['removable'])) {
            $lineItemArray['removable'] = (bool) $lineItemArray['removable'];
        } elseif (isset($defaultValues['removable'])) {
            $lineItemArray['removable'] = $defaultValues['removable'];
        }

        if (isset($lineItemArray['priceDefinition']) && isset($lineItemArray['priceDefinition']['quantity'])) {
            $lineItemArray['priceDefinition']['quantity'] = (int) $lineItemArray['priceDefinition']['quantity'];
        }

        if (isset($lineItemArray['priceDefinition']) && isset($lineItemArray['priceDefinition']['isCalculated'])) {
            $lineItemArray['priceDefinition']['isCalculated'] = (int) $lineItemArray['priceDefinition']['isCalculated'];
        }

        return $lineItemArray;
    }

    #[Route(path: '/test/minimal-off-canvas/cross-selling', name: 'frontend.test.cross-selling', methods: ['GET'])]
    public function cart(SalesChannelContext $context): Response{
        $cartProductId = 1;
        // Fetch cross-selling products for the cart
        $crossSellingProducts = $this->customCrossSellingService->getCrossSellingProducts(
            $cartProductId,
            $context
        );

        // Render the off-canvas cart with cross-selling products
        return $this->renderStorefront('@MyPlugin/storefront/page/example.html.twig', [
            'param' => 'value',
            'crossSellingProducts' => $crossSellingProducts
        ]);
    }

}
