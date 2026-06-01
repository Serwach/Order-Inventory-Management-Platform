# Domain Event Catalog

All events extend `App\Shared\Domain\Event\DomainEvent` and carry:
- `eventId` — UUIDv7 (unique per event instance)
- `occurredAt` — DateTimeImmutable (UTC)
- `eventName()` — dot-notation string

---

## Organization BC

| Event | Name | Transport | Subscribers |
|-------|------|-----------|-------------|
| `OrganizationCreated` | `organization.created` | async | — |
| `UserRegistered` | `organization.user.registered` | async | `SendWelcomeEmailOnUserRegistered` |
| `UserInvited` | `organization.user.invited` | async | email handler |

## Catalog BC

| Event | Name | Transport | Subscribers |
|-------|------|-----------|-------------|
| `ProductCreated` | `catalog.product.created` | async | `IndexProductOnProductCreated` |
| `ProductUpdated` | `catalog.product.updated` | async | `IndexProductOnProductUpdated` |

## Inventory BC

| Event | Name | Transport | Subscribers |
|-------|------|-----------|-------------|
| `InventoryAdjusted` | `inventory.adjusted` | async | — |
| `StockReserved` | `inventory.stock.reserved` | async | — |
| `ReservationReleased` | `inventory.reservation.released` | async | — |

## Order BC

| Event | Name | Transport | Subscribers |
|-------|------|-----------|-------------|
| `OrderCreated` | `order.created` | async | `ReserveInventoryOnOrderCreated` |
| `OrderConfirmed` | `order.confirmed` | async | — |
| `OrderCancelled` | `order.cancelled` | async | `ReleaseInventoryOnOrderCancelled` |
| `PaymentConfirmed` | `order.payment.confirmed` | async | `GenerateInvoiceOnPaymentConfirmed` |
| `ShipmentCreated` | `order.shipment.created` | async | `FulfilInventoryOnShipmentCreated` |
| `InvoiceGenerated` | `order.invoice.generated` | async | email handler |

---

## Event Payload Contract

All events serialize to:

```json
{
  "event_id": "018eefce-0000-7000-abcd-000000000001",
  "event_name": "order.created",
  "occurred_at": "2024-01-15T10:30:00.000+00:00",
  "payload": {
    "order_id": "...",
    "organization_id": "...",
    ...
  }
}
```

Events are immutable after construction. Consumers must be idempotent (event can be redelivered on retry).
