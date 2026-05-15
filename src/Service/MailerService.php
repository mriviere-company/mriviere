<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\CallbackRequest;
use App\Entity\ContactMessage;
use App\Entity\Quote;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final readonly class MailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromEmail,
        private string $adminEmail,
    ) {
    }

    public function sendContactNotification(ContactMessage $msg): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, 'rivierematthieu.com'))
            ->to($this->adminEmail)
            ->replyTo(new Address($msg->getEmail(), $msg->getName()))
            ->subject(sprintf('Nouveau message de %s', $msg->getName()))
            ->htmlTemplate('emails/contact_admin.html.twig')
            ->textTemplate('emails/contact_admin.txt.twig')
            ->context(['message' => $msg]);

        $this->mailer->send($email);
    }

    public function sendQuoteClientReceipt(Quote $quote): void
    {
        $isEnglish = $quote->getLocale() === 'en';
        $subject = $isEnglish
            ? 'Your quote request — rivierematthieu.com'
            : 'Votre demande de devis — rivierematthieu.com';
        $suffix = $isEnglish ? '_en' : '';

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, 'Matthieu Rivière'))
            ->to(new Address($quote->getClientEmail(), $quote->getClientName()))
            ->subject($subject)
            ->htmlTemplate("emails/quote_client{$suffix}.html.twig")
            ->textTemplate("emails/quote_client{$suffix}.txt.twig")
            ->context(['quote' => $quote]);

        $this->mailer->send($email);
    }

    public function sendQuoteAdminNotification(Quote $quote): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, 'rivierematthieu.com'))
            ->to($this->adminEmail)
            ->replyTo(new Address($quote->getClientEmail(), $quote->getClientName()))
            ->subject(sprintf('Nouveau devis %s — %s', $quote->getPackage()->getSlug()->value, $quote->getClientName()))
            ->htmlTemplate('emails/quote_admin.html.twig')
            ->textTemplate('emails/quote_admin.txt.twig')
            ->context(['quote' => $quote]);

        $this->mailer->send($email);
    }

    public function sendCallbackNotification(CallbackRequest $cb): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, 'rivierematthieu.com'))
            ->to($this->adminEmail)
            ->subject(sprintf('Demande de rappel — %s', $cb->getName()))
            ->htmlTemplate('emails/callback_admin.html.twig')
            ->textTemplate('emails/callback_admin.txt.twig')
            ->context(['callback' => $cb]);

        // Reply-to seulement si le client a laissé un email — il est optionnel
        // car la demande est avant tout pour un appel téléphonique.
        if ($cb->getEmail() !== '') {
            $email->replyTo(new Address($cb->getEmail(), $cb->getName()));
        }

        $this->mailer->send($email);
    }
}
