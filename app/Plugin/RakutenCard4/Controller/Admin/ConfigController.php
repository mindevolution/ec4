<?php

namespace Plugin\RakutenCard4\Controller\Admin;

use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Payment;
use Eccube\Repository\PaymentRepository;
use Plugin\RakutenCard4\Common\ConstantCard;
use Plugin\RakutenCard4\Common\ConstantConfig;
use Plugin\RakutenCard4\Entity\Config;
use Plugin\RakutenCard4\Form\Type\Admin\ConfigType;
use Plugin\RakutenCard4\Repository\ConfigRepository;
use Plugin\RakutenCard4\Service\Method\Convenience;
use Plugin\RakutenCard4\Service\Method\CreditCard;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ConfigController extends AbstractController
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;


    /**
     * ConfigController constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(ConfigRepository  $configRepository,
                                PaymentRepository $paymentRepository)
    {
        $this->configRepository = $configRepository;
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/rakuten_card4/config", name="rakuten_card4_admin_config")
     * @Template("@RakutenCard4/admin/config.twig")
     */
    public function index(Request $request)
    {
        /** @var Config $Config */
        $Config = $this->configRepository->get();
        // 初期データの設定
        if (is_null($Config->getConnectionMode())){
            $Config->setConnectionMode(ConstantConfig::CONNECTION_MODE_STG);
        }
        if (is_null($Config->getCardBuyApi())){
            $Config->setCardBuyApi(ConstantCard::BUY_API_AUTHORIZE);
        }
        if (is_null($Config->getCard3dSecureUse())){
            $Config->setCard3dSecureUse(Constant::DISABLED);
        }
        if (is_null($Config->getCardCvvUse())){
            $Config->setCardCvvUse(Constant::DISABLED);
        }
        if (is_null($Config->getCardChallengeType())){
            $Config->setCardChallengeType(ConstantCard::CHALLENGE_DEFAULT);
        }
        $form = $this->createForm(ConfigType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Config $Config */
            $Config = $form->getData();
            $cvs_kind = $form->get('cvs_kind');

            $Config->setEncodeCvsKind($cvs_kind->getData());

            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);

            // カード決済登録
            $this->registerCardPayment($Config);
            // コンビニ
            $this->registerCvsPayment($Config);

            $this->addSuccess('rakuten_card4.admin.save.success', 'admin');

        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @param Config $Config
     * @return void
     */
    private function registerCardPayment($Config)
    {
        if (count($Config->getCardInstallmentsEx()) > 0) {
            $this->createPayment(trans('rakuten_card4.admin.order_edit.payment_card'), CreditCard::class);
        } else {
            $this->enablePayment(CreditCard::class);
        }
    }

    /**
     * @param Config $Config
     * @return void
     */
    private function registerCvsPayment($Config)
    {
        $CvsKind = $Config->getDecodeCvsKindcode();
        if (!empty($CvsKind)) {
            $this->createPayment(
                trans('rakuten_card4.admin.order_edit.payment_cvs'),
                Convenience::class,
                0,
                299999
            );
        } else {
            $this->enablePayment(Convenience::class);
        }
    }

    /**
     * 支払方法の作成
     * @param $methodName
     * @param $findClass
     */
    private function createPayment($methodName, $findClass, $ruleMin = null, $ruleMax = null )
    {
        $Payment = $this->paymentRepository->findOneBy([], ['sort_no' => 'DESC']);
        $sortNo = $Payment ? $Payment->getSortNo() + 1 : 1;

        $Payment = $this->paymentRepository->findOneBy(['method_class' => $findClass]);
        if ($Payment) {
            return;
        }

        $Payment = new Payment();
        $Payment->setCharge(0);
        $Payment->setSortNo($sortNo);
        $Payment->setVisible(true);
        $Payment->setMethod($methodName);
        $Payment->setMethodClass($findClass);
        $Payment->setRuleMin($ruleMin);
        $Payment->setRuleMax($ruleMax);

        $this->entityManager->persist($Payment);
        $this->entityManager->flush($Payment);
    }

    /**
     * 支払方法の非表示
     * @param $findClass
     */
    private function enablePayment($findClass)
    {

        $Payment = $this->paymentRepository->findOneBy(['method_class' => $findClass]);
        if (empty($Payment)) {
            return;
        }
        $Payment->setVisible(false);
        $this->entityManager->persist($Payment);
        $this->entityManager->flush($Payment);
    }

}
