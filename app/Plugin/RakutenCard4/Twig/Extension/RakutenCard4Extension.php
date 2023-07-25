<?php

namespace Plugin\RakutenCard4\Twig\Extension;

use Eccube\Entity\Customer;
use Plugin\RakutenCard4\Common\EccubeConfigEx;
use Plugin\RakutenCard4\Entity\Config;
use Plugin\RakutenCard4\Entity\Rc4OrderPayment;
use Plugin\RakutenCard4\Repository\ConfigRepository;
use Plugin\RakutenCard4\Service\CardService;
use Plugin\RakutenCard4\Service\ConvenienceService;
use Plugin\RakutenCard4\Service\UserTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RakutenCard4Extension extends AbstractExtension
{
    use UserTrait;

    /** @var CardService */
    protected $cardService;
    /** @var ConvenienceService */
    protected $convenienceService;
    /** @var EccubeConfigEx */
    protected $eccubeConfig;
    /** @var Config */
    protected $config;

    /**
     * @param ContainerInterface $container
     * @param CardService $cardService
     * @param ConvenienceService $convenienceService
     * @param EccubeConfigEx $eccubeConfig
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        ContainerInterface $container,
        CardService $cardService,
        ConvenienceService $convenienceService,
        EccubeConfigEx $eccubeConfig,
        ConfigRepository $configRepository
    )
    {
        $this->container = $container;
        $this->cardService = $cardService;
        $this->convenienceService = $convenienceService;
        $this->eccubeConfig = $eccubeConfig;
        $this->config = $configRepository->get();
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('able_register_card', [$this, 'ableRegisterCard']),
            new TwigFunction('get_register_count', [$this, 'getRegisterCount']),
            new TwigFunction('is_cvv_use', [$this, 'isCvvUse']),
            new TwigFunction('get_error_message_for_order_payment', [$this, 'getErrorMessageForOrderPayment']),
        ];
    }

    /**
     * カード登録可能かどうか
     *
     * @return bool true: カード登録可能
     */
    public function ableRegisterCard()
    {
        /** @var Customer $Customer */
        $Customer = $this->getUser();
        return $this->cardService->ableRegisterCard($Customer);
    }

    /**
     * カード登録上限
     *
     * @return int
     */
    public function getRegisterCount()
    {
        return $this->eccubeConfig->card_register_count();
    }

    /**
     * セキュリティコードを使用するかどうか
     *
     * @return bool セキュリティコードを使用する
     */
    public function isCvvUse()
    {
        return $this->config->isCardCvvUse();
    }

    /**
     * エラーメッセージの取得
     *
     * @param Rc4OrderPayment $OrderPayment
     * @return string
     */
    public function getErrorMessageForOrderPayment($OrderPayment)
    {
        if (is_null($OrderPayment)){
            return '';
        }

        if ($OrderPayment->isCard()){
            return $this->cardService->getErrorMessage($OrderPayment->getErrorCode());
        }else if ($OrderPayment->isConenience()){
            return $this->convenienceService->getErrorMessage($OrderPayment->getErrorCode());
        }

        return '';
    }
}
