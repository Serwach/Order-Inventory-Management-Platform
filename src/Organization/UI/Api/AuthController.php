<?php

declare(strict_types=1);

namespace App\Organization\UI\Api;

use App\Organization\Application\Command\CreateOrganization\CreateOrganizationCommand;
use App\Organization\Application\Command\RegisterUser\RegisterUserCommand;
use App\Shared\Application\Command\CommandBusInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/auth')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ValidatorInterface $validator,
    ) {}

    /** Intercepted by LexikJWT security firewall — this body never executes. */
    #[Route('/login', name: 'auth_login', methods: ['POST'])]
    public function login(): never
    {
        throw new \LogicException('Handled by LexikJWT firewall.');
    }

    #[Route('/register', name: 'auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = $request->toArray();

        $violations = $this->validator->validate($data, new Assert\Collection([
            'organization_name' => [new Assert\NotBlank(), new Assert\Length(min: 2, max: 150)],
            'plan'              => [new Assert\NotBlank(), new Assert\Choice(['starter', 'growth', 'enterprise'])],
            'email'             => [new Assert\NotBlank(), new Assert\Email()],
            'password'          => [new Assert\NotBlank(), new Assert\Length(min: 8)],
            'first_name'        => [new Assert\NotBlank(), new Assert\Length(max: 100)],
            'last_name'         => [new Assert\NotBlank(), new Assert\Length(max: 100)],
        ]));

        if (count($violations) > 0) {
            return new JsonResponse(
                ['errors' => $this->serializeViolations($violations)],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $this->commandBus->dispatch(new CreateOrganizationCommand(
            name: $data['organization_name'],
            plan: $data['plan'],
            ownerEmail: $data['email'],
            ownerPassword: $data['password'],
            ownerFirstName: $data['first_name'],
            ownerLastName: $data['last_name'],
        ));

        return new JsonResponse(
            ['message' => 'Organization registered. Please login to get your access token.'],
            Response::HTTP_CREATED
        );
    }

    /**
     * @param \Symfony\Component\Validator\ConstraintViolationListInterface $violations
     * @return array<string, string[]>
     */
    private function serializeViolations(iterable $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $field           = trim($violation->getPropertyPath(), '[]');
            $errors[$field][] = $violation->getMessage();
        }

        return $errors;
    }
}
