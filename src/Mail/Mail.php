<?php

declare(strict_types=1);

namespace Mileena\Mail;

/**
 * Data Transfer Object for email messages.
 *
 * @template TAttachment as string|array{path: string, name?: string}
 */
class Mail
{
    /**
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param string|null $fromName
     * @param string|null $fromEmail
     * @param string|null $replyTo
     * @param list<TAttachment> $attachments
     */
    public function __construct(
        public readonly string $to,
        public readonly string $subject,
        public readonly string $body,
        public readonly ?string $fromName = null,
        public readonly ?string $fromEmail = null,
        public readonly ?string $replyTo = null,
        public readonly ?array $attachments = [],
    ) {}
}
