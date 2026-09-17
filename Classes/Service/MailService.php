<?php

declare(strict_types=1);

namespace Mediadreams\MdNotifications\Service;

/**
 * This file is part of the "Notifications" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 *
 * (c) 2025 Christoph Daecke <typo3@mediadreams.org>
 */

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;

/**
 * Class MailService
 * Wrapper class for sending emails
 */
class MailService
{
    /**
     * Send email and write log, if there is an error
     *
     * @param string $to
     * @param string $subject
     * @param array $data
     * @param string $template
     * @param Request|null $request
     * @return bool
     * @throws TransportExceptionInterface
     */
    public static function sendMail(string $to, string $subject, array $data, string $template, ?Request $request = null): bool
    {
        $email = new FluidEmail();
        $email
            ->to($to)
            ->subject($subject)
            ->format(FluidEmail::FORMAT_HTML)
            ->setTemplate($template)
            ->assignMultiple($data);

        if ($request) {
            $email->setRequest($request);
        }

        $mailer = GeneralUtility::makeInstance(MailerInterface::class);

        try {
            $mailer->send($email);
            return true;
        } catch (\Exception $e) {
            $logger = GeneralUtility::makeInstance(LogManager::class)
                ->getLogger(__CLASS__);

            $logger->log(
                \TYPO3\CMS\Core\Log\LogLevel::ERROR,
                'sendMail failed!',
                [
                    'Exception' => $e->getMessage(),
                    'to' => $to,
                    'template' => $template,
                    'data' => $data,
                ]
            );

            return false;
        }
    }
}
