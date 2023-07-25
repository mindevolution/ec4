<?php

/*
 * This file is part of ContactManagement4
 *
 * Copyright(c) U-Mebius Inc. All Rights Reserved.
 *
 * https://umebius.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactManagement4\Service;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\BaseInfo;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\MailHistoryRepository;
use Eccube\Repository\MailTemplateRepository;
use Eccube\Util\StringUtil;
use Plugin\ContactManagement4\Entity\ContactReply;

class MailService
{
    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var MailTemplateRepository
     */
    protected $mailTemplateRepository;

    /**
     * @var MailHistoryRepository
     */
    private $mailHistoryRepository;

    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * MailService constructor.
     */
    public function __construct(
        \Swift_Mailer $mailer,
        MailTemplateRepository $mailTemplateRepository,
        MailHistoryRepository $mailHistoryRepository,
        BaseInfoRepository $baseInfoRepository,
        \Twig_Environment $twig,
        EccubeConfig $eccubeConfig,
        EntityManagerInterface $entityManager
    ) {
        $this->mailer = $mailer;
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->mailHistoryRepository = $mailHistoryRepository;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->eccubeConfig = $eccubeConfig;
        $this->twig = $twig;
        $this->entityManager = $entityManager;
    }

    /**
     * @return \Swift_Message
     */
    public function sendReplyMail(ContactReply $contactReply)
    {
        log_info('問い合わせ 返信メール送信開始', $contactReply->toArray());

        $email = explode(',', $contactReply->getEmail());
        $email = array_filter($email, function ($var) {
            return StringUtil::isNotBlank($var);
        });

        $message = (new \Swift_Message())
            ->setSubject('['.$this->BaseInfo->getShopName().'] '.$contactReply->getMailSubject())
            ->setFrom([$this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()])
            ->setTo($email)
            ->setBcc($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04())
            ->setBody($contactReply->getContents());

        $count = $this->mailer->send($message);

        log_info('問い合わせ 返信メール送信完了', $contactReply->toArray());

        return $message;
    }
}
