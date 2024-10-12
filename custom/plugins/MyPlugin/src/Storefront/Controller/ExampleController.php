<?php declare(strict_types=1);

namespace MyPlugin\Storefront\Controller;

use MyPlugin\Core\Content\Example\SalesChannel\AbstractExampleRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class ExampleController extends StorefrontController
{
    private AbstractExampleRoute $route;

    public function __construct(AbstractExampleRoute $route){
        $this->route = $route;
    }

    #[Route(path: '/text/store-api/example', name: 'frontend.test.search', methods: ['GET', 'POST'], defaults: ['XmlHttpRequest' => 'true', '_entity' => 'product'])]
    public function load(Criteria $criteria, SalesChannelContext $context): Response
    {
        return $this->route->load($criteria, $context);
    }

    #[Route(path: '/test/controller', name: 'frontend.test.controller', methods: ['GET'])]
    public function test(Request $request, SalesChannelContext $context): Response
    {
        return $this->renderStorefront('@MyPlugin/storefront/page/example.html.twig', [
            'controller' => 'ExampleController'
        ]);
    }
}
