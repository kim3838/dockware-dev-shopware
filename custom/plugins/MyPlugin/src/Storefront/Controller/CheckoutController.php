<?php declare(strict_types=1);

namespace MyPlugin\Storefront\Controller;

use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Checkout\Cart\Error\PaymentMethodChangedError;
use Shopware\Storefront\Checkout\Cart\Error\ShippingMethodChangedError;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Page\Checkout\Offcanvas\CheckoutOffcanvasWidgetLoadedHook;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class CheckoutController extends StorefrontController
{
    private const REDIRECTED_FROM_SAME_ROUTE = 'redirected';

    public function __construct(
        private readonly CartService $cartService,
        private readonly OffcanvasCartPageLoader $offcanvasCartPageLoader,
    ){}

    #[Route(path: '/checkout/offcanvas', name: 'frontend.cart.offcanvas', options: ['seo' => false], defaults: ['XmlHttpRequest' => true], methods: ['GET'])]
    public function offcanvas(Request $request, SalesChannelContext $context): Response
    {
        $page = $this->offcanvasCartPageLoader->load($request, $context);

        $this->hook(new CheckoutOffcanvasWidgetLoadedHook($page, $context));

        $cart = $page->getCart();
        $this->addCartErrors($cart);
        $cartErrors = $cart->getErrors();

        if (!$request->query->getBoolean(self::REDIRECTED_FROM_SAME_ROUTE) && $this->routeNeedsReload($cartErrors)) {
            $cartErrors->clear();

            // To prevent redirect loops add the identifier that the request already got redirected from the same origin
            return $this->redirectToRoute(
                'frontend.cart.offcanvas',
                [...$request->query->all(), ...[self::REDIRECTED_FROM_SAME_ROUTE => true]],
            );
        }

        $cartErrors->clear();

        $actionResponseFrom = $request->query->get('action_response_from');
        $uniqueItemAddCount = $request->query->get('unique_item_add_count');
        $lastEngagedProductId = $request->query->get('last_engaged_product_id');

        $lastEngagedProductAction = in_array($actionResponseFrom, ['cart::add-line-item']);

        $showOnlyLastEngagedProduct = $lastEngagedProductAction && ($uniqueItemAddCount == 1);

        return $this->renderStorefront('@Storefront/storefront/component/checkout/offcanvas-cart.html.twig', [
            'page' => $page,
            'last_engaged_product_id' => $lastEngagedProductId,
            'show_only_last_engaged_product' => $showOnlyLastEngagedProduct,
        ]);
    }

    private function routeNeedsReload(ErrorCollection $cartErrors): bool
    {
        foreach ($cartErrors as $error) {
            if ($error instanceof ShippingMethodChangedError || $error instanceof PaymentMethodChangedError) {
                return true;
            }
        }

        return false;
    }
}