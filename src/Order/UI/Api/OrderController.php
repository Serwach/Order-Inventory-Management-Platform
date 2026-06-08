<?php

declare(strict_types=1);

namespace App\Order\UI\Api;

use App\Order\Application\Command\CancelOrder\CancelOrderCommand;
use App\Order\Application\Command\ConfirmOrder\ConfirmOrderCommand;
use App\Order\Application\Command\ConfirmPayment\ConfirmPaymentCommand;
use App\Order\Application\Command\PlaceOrder\PlaceOrderCommand;
use App\Order\Application\Query\GetOrder\GetOrderQuery;
use App\Order\Application\Query\ListOrders\ListOrdersQuery;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Entity\OrderItem;
use App\Shared\Application\Command\CommandBusInterface;
use App\Shared\Application\Query\QueryBusInterface;
use App\Shared\Domain\Repository\PaginatedResult;
use App\Shared\Infrastructure\Security\CurrentUserProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/orders')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class OrderController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly CurrentUserProvider $currentUser,
        private readonly int $defaultPaginationLimit,
    ) {}

    #[Route('', name: 'orders_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        /** @var PaginatedResult<Order> $result */
        $result = $this->queryBus->ask(new ListOrdersQuery(
            organizationId: $this->currentUser->getOrganizationId(),
            customerId: $request->query->get('customer_id'),
            status: $request->query->get('status'),
            from: $request->query->get('from'),
            to: $request->query->get('to'),
            page: (int) $request->query->get('page', 1),
            limit: (int) $request->query->get('limit', $this->defaultPaginationLimit),
            sortBy: $request->query->get('sort_by', 'placedAt'),
            sortDir: $request->query->get('sort_dir', 'desc'),
        ));

        return new JsonResponse([
            'data'       => array_map($this->serializeOrder(...), $result->items),
            'pagination' => [
                'total'    => $result->totalCount,
                'page'     => $result->page,
                'limit'    => $result->limit,
                'pages'    => $result->totalPages(),
                'has_next' => $result->hasNextPage(),
                'has_prev' => $result->hasPreviousPage(),
            ],
        ]);
    }

    #[Route('/{id}', name: 'orders_get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        /** @var Order $order */
        $order = $this->queryBus->ask(new GetOrderQuery(
            orderId: $id,
            organizationId: $this->currentUser->getOrganizationId(),
        ));

        return new JsonResponse($this->serializeOrder($order));
    }

    #[Route('', name: 'orders_place', methods: ['POST'])]
    public function place(Request $request): JsonResponse
    {
        $data     = $request->toArray();
        $currency = $data['currency'] ?? 'USD';

        $items = array_map(static fn (array $i) => [
            'productId'      => $i['product_id'] ?? $i['productId'] ?? '',
            'variantId'      => $i['variant_id'] ?? $i['variantId'] ?? null,
            'sku'            => $i['sku'] ?? '',
            'name'           => $i['name'] ?? '',
            'quantity'       => (int) ($i['quantity'] ?? 1),
            'unitPriceAmount'=> (int) ($i['unit_price'] ?? $i['unitPriceAmount'] ?? 0),
            'currency'       => $i['currency'] ?? $currency,
        ], $data['items'] ?? []);

        $this->commandBus->dispatch(new PlaceOrderCommand(
            organizationId: $this->currentUser->getOrganizationId(),
            customerId: $data['customer_id'] ?? $this->currentUser->getUserId(),
            items: $items,
            shippingAddress: $data['shipping_address'] ?? [],
            currency: $currency,
            notes: $data['notes'] ?? null,
        ));

        return new JsonResponse(
            ['message' => 'Order placed. Inventory reservation in progress.'],
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}/confirm', name: 'orders_confirm', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function confirm(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new ConfirmOrderCommand(
            orderId: $id,
            organizationId: $this->currentUser->getOrganizationId(),
        ));

        return new JsonResponse(['message' => 'Order confirmed.']);
    }

    #[Route('/{id}/payment', name: 'orders_confirm_payment', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function confirmPayment(string $id, Request $request): JsonResponse
    {
        $data = $request->toArray();

        $this->commandBus->dispatch(new ConfirmPaymentCommand(
            orderId: $id,
            organizationId: $this->currentUser->getOrganizationId(),
            paymentId: $data['payment_id'] ?? '',
        ));

        return new JsonResponse(['message' => 'Payment confirmed.']);
    }

    #[Route('/{id}/cancel', name: 'orders_cancel', methods: ['POST'])]
    public function cancel(string $id, Request $request): JsonResponse
    {
        $data = $request->toArray();

        $this->commandBus->dispatch(new CancelOrderCommand(
            orderId: $id,
            organizationId: $this->currentUser->getOrganizationId(),
            reason: $data['reason'] ?? 'Customer request',
        ));

        return new JsonResponse(['message' => 'Order cancelled.']);
    }

    /** @return array<string, mixed> */
    private function serializeOrder(Order $order): array
    {
        return [
            'id'               => $order->id()->value(),
            'number'           => $order->number()->value(),
            'status'           => $order->status()->value,
            'status_label'     => $order->status()->label(),
            'customer_id'      => $order->customerId(),
            'total'            => [
                'amount'    => $order->total()->amount(),
                'currency'  => $order->total()->currency(),
                'formatted' => $order->total()->formatted(),
            ],
            'items'            => $order->items()->map(fn (OrderItem $i) => [
                'id'           => $i->id(),
                'product_id'   => $i->productId(),
                'sku'          => $i->sku(),
                'name'         => $i->productName(),
                'quantity'     => $i->quantity(),
                'unit_price'   => $i->unitPrice()->amount(),
                'line_total'   => $i->lineTotal()->amount(),
                'currency'     => $i->currency(),
            ])->toArray(),
            'shipping_address' => $order->shippingAddress(),
            'notes'            => $order->notes(),
            'placed_at'        => $order->placedAt()->format(\DateTimeInterface::RFC3339),
            'confirmed_at'     => $order->confirmedAt()?->format(\DateTimeInterface::RFC3339),
            'paid_at'          => $order->paidAt()?->format(\DateTimeInterface::RFC3339),
            'cancelled_at'     => $order->cancelledAt()?->format(\DateTimeInterface::RFC3339),
        ];
    }
}
