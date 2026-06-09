<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Catalog\Application\Command\CreateProduct\CreateProductCommand;
use App\Inventory\Application\Command\AdjustStock\AdjustStockCommand;
use App\Order\Application\Command\PlaceOrder\PlaceOrderCommand;
use App\Organization\Application\Command\CreateOrganization\CreateOrganizationCommand;
use App\Shared\Application\Command\CommandBusInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

final class AppFixtures extends Fixture
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // ── Organization + Owner ──────────────────────────────────────────────
        $this->commandBus->dispatch(new CreateOrganizationCommand(
            name: 'Acme Corp',
            plan: 'growth',
            ownerEmail: 'owner@acme-corp.dev',
            ownerPassword: 'password123',
            ownerFirstName: 'Alice',
            ownerLastName: 'Smith',
        ));

        $this->commandBus->dispatch(new CreateOrganizationCommand(
            name: 'Beta Supplies',
            plan: 'starter',
            ownerEmail: 'owner@beta-supplies.dev',
            ownerPassword: 'password123',
            ownerFirstName: 'Bob',
            ownerLastName: 'Jones',
        ));

        // ── Hint for demo users ───────────────────────────────────────────────
        echo "\n\033[32m✓ Fixtures loaded:\033[0m\n";
        echo "  Login: owner@acme-corp.dev / password123\n";
        echo "  Login: owner@beta-supplies.dev / password123\n\n";
    }
}
