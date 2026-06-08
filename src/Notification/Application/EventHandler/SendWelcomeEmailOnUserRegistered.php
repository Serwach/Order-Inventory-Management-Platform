<?php

declare(strict_types=1);

namespace App\Notification\Application\EventHandler;

use App\Organization\Domain\Event\UserRegistered;
use App\Shared\Application\Event\EventHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler(bus: 'event.bus')]
final class SendWelcomeEmailOnUserRegistered implements EventHandlerInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(UserRegistered $event): void
    {
        try {
            $email = (new Email())
                ->from('noreply@oimp.io')
                ->to($event->email)
                ->subject('Welcome to OIMP Platform')
                ->html(sprintf(
                    '<h1>Welcome, %s!</h1><p>Your account has been created. Start managing your orders and inventory today.</p>',
                    htmlspecialchars($event->firstName)
                ));

            $this->mailer->send($email);

            $this->logger->info('Welcome email sent', [
                'user_id'    => $event->userId,
                'email'      => $event->email,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send welcome email', [
                'user_id' => $event->userId,
                'error'   => $e->getMessage(),
            ]);

            // Don't re-throw — welcome email failure should not roll back the transaction
        }
    }
}
