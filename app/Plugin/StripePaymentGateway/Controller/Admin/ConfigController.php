<?php
/*
* Plugin Name : StripePaymentGateway
*
* Copyright (C) 2018 Subspire Inc. All Rights Reserved.
* http://www.subspire.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/


namespace Plugin\StripePaymentGateway\Controller\Admin;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Payment;
use Eccube\Repository\PaymentRepository;
use Plugin\StripePaymentGateway\Form\Type\Admin\StripeConfigType;
use Plugin\StripePaymentGateway\Form\Type\Admin\LicenseInputType;
use Plugin\StripePaymentGateway\Repository\StripeConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Plugin\StripePaymentGateway\Entity\LicenseKey;
use Plugin\StripePaymentGateway\Service\Method\StripeKonbini;

class ConfigController extends AbstractController
{
    /**
     * @var StripeConfigRepository
     */
    protected $stripeConfigRepository;
    protected $router;
    protected $util_service;
    protected $em;
    protected $container;
    protected $paymentRepository;

    /**
     * ConfigController constructor.
     *
     * @param StripeConfigRepository $stripeConfigRepository
     */
    public function __construct(StripeConfigRepository $stripeConfigRepository,
        UrlGeneratorInterface $router,
        ContainerInterface $container,
        PaymentRepository $paymentRepository)
    {
        $this->stripeConfigRepository = $stripeConfigRepository;
        $this->router = $router;
        $this->container = $container;
        $this->util_service = $this->container->get("plg_stripe_payment.service.util");
        $this->em = $this->container->get("doctrine.orm.entity_manager");
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/stripe_payment_gateway/config", name="stripe_payment_gateway_admin_config")
     * @Template("@StripePaymentGateway/admin/stripe_config.twig")
     */
    public function index(Request $request)
    { 
        $StripeConfig = $this->stripeConfigRepository->get();
        $form = $this->createForm(StripeConfigType::class, $StripeConfig);
        $form->handleRequest($request);

        $form_license = $this->createForm(LicenseInputType::class);
        $form_license->handleRequest($request);
        
        if ($form_license->isSubmitted() && $form_license->isValid()) {           
            $key = $form_license->getData();
            $key->setInstance(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'));
            $result = $this->util_service->requestLicense($key);
            if ($result === true){               
                $key->setKeyType(2);
                $this->saveKey($key);
                $this->addSuccess('stripe_payment_gateway.admin.license.success', 'admin');
                
                return [
                    'form' => $form->createView(),
                    'form_license' => $form_license->createView(),
                    'licensed' => 1,
                    'product_licensed' => 2,
                ];
            } else {    
                $this->addError('stripe_payment_gateway.admin.license.failed', 'admin');
                return [
                    'form' => $form->createView(),
                    'form_license' => $form_license->createView(),
                    'licensed' => 0,
                ];
            }       
        }
        // ===================
        
        
        $lic_res = $this->util_service->checkLicense();
        
        $StripeConfig = $this->stripeConfigRepository->get();
        $form = $this->createForm(StripeConfigType::class, $StripeConfig);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $StripeConfig = $form->getData();
            if($lic_res === "unauthed"){
                $this->addError('stripe_payment_gateway.admin.license.failed', 'admin');
                return [
                    'form' => $form->createView(),
                    'form_license' => $form_license->createView(),
                    'licensed' => 0,
                ];
            }
            if($lic_res === "test"){
                $pub_key = $StripeConfig->getPublishableKey();
                $sec_key = $StripeConfig->getSecretKey();
                if (substr($pub_key, 0, 7) != 'pk_test' || substr($sec_key, 0, 7) != 'sk_test') {
                    $this->addError('stripe_payment_gateway.admin.license.stripe.testkey', 'admin');
                    return  [
                        'form' => $form->createView(),
                        'form_license' => $form_license->createView(),
                        'licensed' => 1,
                        'product_licensed' => 1,
                    ];
                }
            }

            if ($StripeConfig->getKonbiniEnable()) {
                $Payment = $this->paymentRepository->findOneBy(['method_class' => StripeKonbini::class]);
                if (empty($Payment)) {
                    $lastPayment = $this->paymentRepository->findOneBy([], ['sort_no' => 'DESC']);
                    $sortNo = $lastPayment ? $lastPayment->getSortNo() + 1 : 1;

                    $Payment = new Payment();
                    $Payment->setCharge(0);
                    $Payment->setSortNo($sortNo);
                    $Payment->setVisible(true);
                    
                    $Payment->setMethodClass(StripeKonbini::class);
                    $Payment->setMethod('コンビニ払い');
                }
                $Payment->setVisible(true);
                
                $this->entityManager->persist($Payment);
                $this->entityManager->flush($Payment);
            }
            
            $this->entityManager->persist($StripeConfig);
            $this->entityManager->flush($StripeConfig);

            $this->addSuccess('stripe_payment_gateway.admin.save.success', 'admin');
            return $this->redirectToRoute('stripe_payment_gateway_admin_config');
        }
        // =================
        if($lic_res == "test"){
            return  [
                'form' => $form->createView(),
                'form_license' => $form_license->createView(),
                'licensed' => 1,
                'product_licensed' => 1,
            ];
        }
        if($lic_res == "authed"){
            return [
                'form' => $form->createView(),
                'form_license' => $form_license->createView(),
                'licensed' => 1,
                'product_licensed' => 2,
            ];
        }
        if($lic_res == "unauthed"){
            return [
                'form' => $form->createView(),
                'form_license' => $form_license->createView(),
                'licensed' => 0,
            ];
        }
        
    }

     /**
     * @Route("/%eccube_admin_route%/stripe_payment_gateway/config/test", name="stripe_payment_gateway_admin_test_config")
     * @Template("@StripePaymentGateway/admin/stripe_config.twig")
     */
    public function testEnvSetting() {
        $test_key = new LicenseKey;
        $test_key
            ->setEmail("testemail@email.com")
            ->setLicenseKey("test_key")
            ->setInstance(111)
            ->setKeyType(1);
        
        // (
        //     'email' => 'testemail@email.com',
        //     'license_key' => 'test_key',
        //     'key_type' => 1,
        //     'instance' => 111,
        // ); 
        $this->saveKey($test_key);
        return $this->redirectToRoute('stripe_payment_gateway_admin_config');
    }

    public function saveKey($key){
        $key_prev = $this->em->getRepository(LicenseKey::class)->get();
        if($key_prev && $key !== $key_prev){
            $this->em->remove($key_prev);
        }
        $this->em->persist($key);
        $this->em->flush();
        $this->em->commit();
    }
}