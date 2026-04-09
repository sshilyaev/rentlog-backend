<?php

declare(strict_types=1);

namespace App\Auth\Application\Service;

use App\Auth\Domain\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class AuthEmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $mailerFrom,
        private readonly string $publicBaseUrl,
    ) {
    }

    public function sendEmailVerification(User $user, string $plainToken): void
    {
        $url = rtrim($this->publicBaseUrl, '/').'/api/v1/auth/verify-email?token='.urlencode($plainToken);

        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($user->getEmail())
            ->subject('Подтверждение email — Rentlog')
            ->text(
                "Здравствуйте, {$user->getFullName()}!\n\n".
                "Подтвердите адрес электронной почты, перейдя по ссылке:\n{$url}\n\n".
                "Если вы не регистрировались в Rentlog, проигнорируйте это письмо.\n"
            );

        $this->mailer->send($email);
    }

    public function sendPasswordReset(User $user, string $plainToken): void
    {
        // Ссылка на клиент (веб/диплинк): экран ввода нового пароля и POST /api/v1/auth/reset-password
        $url = rtrim($this->publicBaseUrl, '/').'/reset-password?token='.urlencode($plainToken);

        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($user->getEmail())
            ->subject('Сброс пароля — Rentlog')
            ->text(
                "Здравствуйте, {$user->getFullName()}!\n\n".
                "Для сброса пароля перейдите по ссылке (действует ограниченное время):\n{$url}\n\n".
                "Если вы не запрашивали сброс, проигнорируйте письмо.\n"
            );

        $this->mailer->send($email);
    }
}
