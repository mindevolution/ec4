<?php

namespace Plugin\SoftbankPayment4\Service;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Payment;
use Eccube\Repository\PaymentRepository;
use Plugin\SoftbankPayment4\Entity\PayMethod;
use Plugin\SoftbankPayment4\Entity\Master\PayMethodType;
use Plugin\SoftbankPayment4\Repository\PayMethodRepository;

class PayMethodHelper
{
    public function __construct(
        EntityManagerInterFace $em,
        PaymentRepository $paymentRepository,
        PayMethodRepository $payMethodRepository
    )
    {
        $this->em = $em;
        $this->paymentRepository = $paymentRepository;
        $this->payMethodRepository = $payMethodRepository;
    }

    /**
     * フォーム入力値に合わせて 新規登録 / 有効化 / 無効化 を行う.
     *
     * @param array $inputs
     *
     * @return void
     */
    public function commitPayMethods(array $inputs, $Config)
    {
        $this->disablePayMethods($inputs);
        $this->enablePayMethods($inputs, $Config);
        $this->em->flush();
    }

    private function disablePayMethods(array $inputs)
    {
        $master = PayMethodType::getCodes();
        $diff = array_diff($master, $inputs);

        foreach ($diff as $code) {
            $PayMethod = $this->payMethodRepository->find($code);
            if($PayMethod !== null) {
                $PayMethod->setEnable(false);
            }
            $method_class = PayMethodType::$class[$code];
            $Payment = $this->paymentRepository->findOneBy(['method_class' => $method_class]);
            if($Payment !== null) {
                $Payment->setVisible(false);
            }
        }
    }

    private function enablePayMethods(array $inputs, $Config)
    {
        $StoredPayment = $this->paymentRepository->findOneBy([], ['sort_no' => 'DESC']);
        $sortNo = $StoredPayment->getSortNo();

        foreach ($inputs as $code) {
            $PayMethod = $this->payMethodRepository->find($code);
            if($PayMethod === null) {
                // 新規追加
                $PayMethod = new Paymethod();
                $PayMethod
                    ->setCode($code)
                    ->setEnable(true)
                    ->setConfig($Config);

                $Config->addPayMethod($PayMethod);
            } else {
                $PayMethod->setEnable(true);
            }

            $method_class = PayMethodType::$class[$code];
            $Payment = $this->paymentRepository->findOneBy(['method_class' => $method_class]);
            if($Payment === null) {
                $sortNo++;

                $Payment = new Payment();
                $Payment
                    ->setMethod(PayMethodType::$name[$code])
                    ->setSortNo($sortNo)
                    ->setMethodClass($method_class)
                    ->setVisible(true);
                    if (PayMethodType::getCodeByClass($method_class) == PayMethodType::CVS_DEFERRED_API) {
                        $Payment->setRuleMin(1);
                    } else {
                        $Payment->setRuleMin(2);
                    }
                $this->em->persist($Payment);

            } else {
                $Payment->setVisible(true);
            }
        }
    }
}
