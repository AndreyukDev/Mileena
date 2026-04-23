<?php

declare(strict_types=1);

namespace Mileena\Mail;

interface MailService
{
    /**
     * @param Mail $mail
     * @return bool
     */
    public function send(Mail $mail): bool;
}
