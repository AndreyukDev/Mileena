<?php

declare(strict_types=1);

namespace Mileena\Mail;

use Exception;
use Mileena\Web\WebApp;
use PHPMailer\PHPMailer\PHPMailer;

class PhpMailerDriver implements MailService
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);

        $app = WebApp::getInstance();

        if ($app->config->get('mailer.isSMTP')) {
            $this->mailer->isSMTP();
            $this->mailer->Host       = $app->config->get('mailer.host');
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = $app->config->get('mailer.user');
            $this->mailer->Password   = $app->config->get('mailer.pass');
            $this->mailer->SMTPSecure = $app->config->get('mailer.encryption');
            $this->mailer->Port       = $app->config->get('mailer.port');
        }

        $this->mailer->setFrom($app->config->get('mailer.fromEmail'), $app->config->get('mailer.fromName'));
        $this->mailer->isHTML(true);
        $this->mailer->CharSet = $app->config->get('mailer.charset');
    }

    /**
     * @param Mail $mail
     * @return bool
     */
    public function send(Mail $mail): bool
    {
        try {
            // clear object
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearReplyTos();

            $this->mailer->addAddress($mail->to);

            if ($mail->replyTo) {
                $this->mailer->addReplyTo($mail->replyTo);
            }

            if ($mail->fromEmail) {
                $this->mailer->setFrom($mail->fromEmail, $mail->fromName ?? '');
            }

            $this->mailer->Subject = $mail->subject;
            $this->mailer->Body    = $mail->body;
            // old school
            $this->mailer->AltBody = strip_tags($mail->body);

            // files
            if (!empty($mail->attachments)) {
                foreach ($mail->attachments as $attachment) {
                    if (is_string($attachment)) {
                        $this->mailer->addAttachment($attachment);
                    } elseif (is_array($attachment) && isset($attachment['path'])) {
                        $name = $attachment['name'] ?? basename($attachment['path']);
                        $this->mailer->addAttachment($attachment['path'], $name);
                    }
                }
            }

            $this->mailer->send();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
