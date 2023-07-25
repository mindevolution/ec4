<?php

namespace Plugin\RakutenCard4\Service;

use Eccube\Common\EccubeConfig;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\MailHistoryRepository;
use Eccube\Repository\MailTemplateRepository;
use Eccube\Service\MailService;
use Plugin\RakutenCard4\Service\Method\Convenience;
use Plugin\RakutenCard4\Service\Method\CreditCard;
use Plugin\RakutenCard4\Util\Rc4LogUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Rc4MailService extends MailService
{
    /** @var BasePaymentService */
    protected $paymentService;

    public function __construct(
        \Swift_Mailer $mailer
        , MailTemplateRepository $mailTemplateRepository
        , MailHistoryRepository $mailHistoryRepository
        , BaseInfoRepository $baseInfoRepository
        , EventDispatcherInterface $eventDispatcher
        , \Twig_Environment $twig
        , EccubeConfig $eccubeConfig
        , BasePaymentService $paymentService
    )
    {
        $this->paymentService = $paymentService;
        parent::__construct($mailer, $mailTemplateRepository, $mailHistoryRepository, $baseInfoRepository, $eventDispatcher, $twig, $eccubeConfig);
    }

    public function sendCaptureError($error, $contents_data, $method_class)
    {
        $process_name = $this->paymentService->getNotificationCaptureName($method_class);
        $common_message = "----------{$process_name}で売上・入金対象外の受注に届いたときのアラート----------";
        $message = $common_message . 'start';
        Rc4LogUtil::info($message, $contents_data);

        $file_name = '@RakutenCard4/Mail/capture_alert.twig';
        $title = "売上対象外の受注に{$process_name}が届きました。";

        switch ($method_class){
            case Convenience::class:
                $alert_message = '返金等の対応をご検討ください。';
                break;
            case CreditCard::class:
            default:
                $alert_message = '決済の管理画面より売上の取消をご検討ください。';
                break;
        }

        $twig_contents = [
            'BaseInfo' => $this->BaseInfo,
            'error' => $error,
            'contents_data' => $contents_data,
            'title' => $title,
            'alert_message' => $alert_message,
        ];
        $body = $this->twig->render($file_name, $twig_contents);
        $message = (new \Swift_Message())
            ->setSubject('['.$this->BaseInfo->getShopName().'] '. $title)
            ->setFrom([$this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()])
            ->setTo([$this->BaseInfo->getEmail01()])
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04());

        // HTMLテンプレートが存在する場合
        $htmlFileName = $this->getHtmlTemplate($file_name);
        if (!is_null($htmlFileName)) {
            $htmlBody = $this->twig->render($htmlFileName, $twig_contents);

            $message
                ->setContentType('text/plain; charset=UTF-8')
                ->setBody($body, 'text/plain')
                ->addPart($htmlBody, 'text/html');
        } else {
            $message->setBody($body);
        }

        $count = $this->mailer->send($message, $failures);

        $contents_data['count'] = $count;
        $message = $common_message . 'メール送信完了';
        Rc4LogUtil::info($message, $contents_data);

        return $count;
    }
}
