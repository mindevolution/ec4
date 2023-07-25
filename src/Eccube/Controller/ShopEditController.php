<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Controller;

use Eccube\Entity\BaseInfo;
use Eccube\Entity\CustomerLevel;
use Eccube\Entity\CustomerShop;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\Front\EntryShopType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\CustomerShopRepository;
use Eccube\Repository\Master\CustomerStatusRepository;
use Eccube\Service\MailService;
use Symfony\Component\Filesystem\Filesystem;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception as HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Eccube\Service\CartService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ShopEditController extends AbstractController
{
    /**
     * @var CustomerStatusRepository
     */
    protected $customerStatusRepository;

    /**
     * @var ValidatorInterface
     */
    protected $recursiveValidator;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var CustomerShopRepository
     */
    protected $customerShopRepository;

    /**
     * @var EncoderFactoryInterface
     */
    protected $encoderFactory;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var \Eccube\Service\CartService
     */
    protected $cartService;

    /**
     * ShopEditController constructor.
     *
     * @param CartService $cartService
     * @param CustomerStatusRepository $customerStatusRepository
     * @param MailService $mailService
     * @param BaseInfoRepository $baseInfoRepository
     * @param CustomerRepository $customerRepository
     * @param EncoderFactoryInterface $encoderFactory
     * @param ValidatorInterface $validatorInterface
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        CartService $cartService,
        CustomerStatusRepository $customerStatusRepository,
        MailService $mailService,
        BaseInfoRepository $baseInfoRepository,
        CustomerRepository $customerRepository,
        CustomerShopRepository $customerShopRepository,
        EncoderFactoryInterface $encoderFactory,
        ValidatorInterface $validatorInterface,
        TokenStorageInterface $tokenStorage
    ) {
        $this->customerStatusRepository = $customerStatusRepository;
        $this->mailService = $mailService;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->customerRepository = $customerRepository;
        $this->customerShopRepository = $customerShopRepository;
        $this->encoderFactory = $encoderFactory;
        $this->recursiveValidator = $validatorInterface;
        $this->tokenStorage = $tokenStorage;
        $this->cartService = $cartService;
    }

    /**
     * 会員登録画面.
     *
     * @Route("/shopEdit", name="shopEdit")
     * @Template("ShopEdit/index.twig")
     */
    public function index(Request $request)
    {
        if (!$this->isGranted('ROLE_USER')) {
            log_info('認証済のためログイン処理をスキップ');

            return $this->redirectToRoute('mypage');
        }
        $Customer = $this->getUser();
        $CustomerShop = $this->customerShopRepository->getCustomerShopByCustomer($Customer);
        /* @var $builder \Symfony\Component\Form\FormBuilderInterface */
        $builder = $this->formFactory->createBuilder(EntryShopType::class, $CustomerShop);

        $event = new EventArgs(
            [
                'builder' => $builder,
                'CustomerShop' => $CustomerShop,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_SHOPEDIT_INDEX_INITIALIZE, $event);

        /* @var $form \Symfony\Component\Form\FormInterface */
        $form = $builder->getForm();
        $oldUploadFile = $CustomerShop->getUploadFile();
        $oldUploadFile2 = $CustomerShop->getUploadFile2();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            switch ($request->get('mode')) {
                case 'complete':

                    $CustomerShop = $form->getData();
                    $file = $form['upload_file']->getData();
                    $fs = new Filesystem();
                    if ($file && $fs->exists($this->getParameter('eccube_temp_image_dir').'/'.$file)) {
                        $fs->rename(
                            $this->getParameter('eccube_temp_image_dir').'/'.$file,
                            $this->getParameter('eccube_save_image_dir').'/'.$file
                        );
                    }
                    $file2 = $form['upload_file2']->getData();
                    $fs2 = new Filesystem();
                    if ($file2 && $fs2->exists($this->getParameter('eccube_temp_image_dir').'/'.$file2)) {
                        $fs2->rename(
                            $this->getParameter('eccube_temp_image_dir').'/'.$file2,
                            $this->getParameter('eccube_save_image_dir').'/'.$file2
                        );
                    }



                    log_info('会員ShopEdit登録開始');

                    $Customer = $this->getUser();
                    $CustomerShop->setStatus("P");
                    $CustomerShop->setCustomer($Customer);


                    $this->entityManager->persist($CustomerShop);
                    $this->entityManager->flush();

                    //edit by gzy
                    // $customerLevel = new CustomerLevel();
                    // $customerLevel->setCustomer($Customer);
                    // $customerLevel->setUpdateDate(new \DateTime());
                    // $customerLevel->setCreateDate(new \DateTime());
                    // $customerLevel->setLevel("BLANK");
                    // $this->entityManager->persist($customerLevel);
                    // $this->entityManager->flush();

                    log_info('会員Shop登録完了');

                    $event = new EventArgs(
                        [
                            'form' => $form,
                            'CustomerShop' => $CustomerShop,
                        ],
                        $request
                    );
                    $this->eventDispatcher->dispatch(EccubeEvents::FRONT_ENTRYSHOP_INDEX_COMPLETE, $event);

                    return $this->redirectToRoute('mypage_shop');
            }
        }

        return [
            'form' => $form->createView(),
            'CustomerShop' => $CustomerShop,
            'oldUploadFile' => $oldUploadFile,
            'oldUploadFile2' => $oldUploadFile2,
        ];
    }

   

    

}
