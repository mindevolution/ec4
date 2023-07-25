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
use Eccube\Entity\CustomerShopLevel;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\Front\EntryShopType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\CustomerShopRepository;
use Eccube\Repository\CustomerShopLevelRepository;
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

class EntryShopController extends AbstractController
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
     * @var CustomerShopLevelRepository
     */
    protected $customerShopLevelRepository;

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
     * EntryShopController constructor.
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
        CustomerShopLevelRepository $customerShopLevelRepository,
        EncoderFactoryInterface $encoderFactory,
        ValidatorInterface $validatorInterface,
        TokenStorageInterface $tokenStorage
    ) {
        $this->customerStatusRepository = $customerStatusRepository;
        $this->mailService = $mailService;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->customerRepository = $customerRepository;
        $this->customerShopRepository = $customerShopRepository;
        $this->customerShopLevelRepository = $customerShopLevelRepository;
        $this->encoderFactory = $encoderFactory;
        $this->recursiveValidator = $validatorInterface;
        $this->tokenStorage = $tokenStorage;
        $this->cartService = $cartService;
    }

    /**
     * 会員登録画面.
     *
     * @Route("/entryShop", name="entryShop")
     * @Template("EntryShop/index.twig")
     */
    public function index(Request $request)
    {
        if (!$this->isGranted('ROLE_USER')) {
            log_info('認証済のためログイン処理をスキップ');

            return $this->redirectToRoute('mypage');
        }

        /** @var $Customer \Eccube\Entity\CustomerShop */
        // $CustomerShop = $this->customerShopRepository->newCustomerShop();
        $CustomerShop = new \Eccube\Entity\CustomerShop();

        /* @var $builder \Symfony\Component\Form\FormBuilderInterface */
        $builder = $this->formFactory->createBuilder(EntryShopType::class, $CustomerShop);

        $event = new EventArgs(
            [
                'builder' => $builder,
                'CustomerShop' => $CustomerShop,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_ENTRYSHOP_INDEX_INITIALIZE, $event);

        /* @var $form \Symfony\Component\Form\FormInterface */
        $form = $builder->getForm();

        $oldUploadFile = $CustomerShop->getUploadFile();
        $oldUploadFile2 = $CustomerShop->getUploadFile2();
        // $form->setData($CustomerShop);

        $form->handleRequest($request);
        // $form->bind($this->request);
        // $valid = $form->isValid();
        // $CustomerShopTemp = $form->getData();
        // if($CustomerShopTemp->getShopName() == null || $CustomerShopTemp->getManager1() == null || $CustomerShopTemp->getAddress() == null ||
        //     $CustomerShopTemp->getTel() == null || $CustomerShopTemp->getEmail() == null || $CustomerShopTemp->getPage() == null ||
        //     $CustomerShopTemp->getManager2() == null || $CustomerShopTemp->getJob() == null || $CustomerShopTemp->getCode() == null ||
        //     $CustomerShopTemp->getRegisterDate() == null || $CustomerShopTemp->getEffectiveDate() == null || $CustomerShopTemp->getUploadFile() == null){
        //     $valid = 0;
        // }

        $valid = $form->isValid();
        if ($form->isSubmitted() && !$form['upload_file']->getData()) { 
            $valid = 0;
        }
        if ($form->isSubmitted() && !$form['upload_file2']->getData()) { 
            $valid = 0;
        }
        

        if ($form->isSubmitted() && $valid) {            
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



                    log_info('会員Shop登録開始');
                    //edit by gzy
                    $CustomerShopLevel = $this->customerShopLevelRepository->findOneBy(['level' => 'GOLD']);
                    $Customer = $this->getUser();
                    $CustomerShop->setStatus("P");
                    $CustomerShop->setCustomer($Customer);
                    $CustomerShop->setCustomerShopLevel($CustomerShopLevel);
                    $CustomerShop->setLastCustomerShopLevel($CustomerShopLevel);
                    $num = sprintf("%05d", $Customer->getId());
                    $CustomerShop->setNum($num);

                    $oneYearDate = date( 'Y-m-d', strtotime("+1 year"));
                    $oneYearDatetomorrow = date( 'Y-m-d', strtotime($oneYearDate . "+1 day"));
                    $dd2 = date( 'Y-m-d H:i:s', strtotime($oneYearDatetomorrow));
                    $CustomerShop->setExpTime($dd2);


                    
                    $this->entityManager->persist($CustomerShop);
                    $this->entityManager->flush();

                    

                    log_info('会員Shop登録完了');
					//edit by gzy 提交后发邮件
                    $this->mailService->sendEntryShopSubmitMail($this->getUser());

                    $event = new EventArgs(
                        [
                            'form' => $form,
                            'CustomerShop' => $CustomerShop,
                        ],
                        $request
                    );
                    $this->eventDispatcher->dispatch(EccubeEvents::FRONT_ENTRYSHOP_INDEX_COMPLETE, $event);

                    return $this->redirectToRoute('mypage_shop');

                    // $activateFlg = $this->BaseInfo->isOptionCustomerActivate();

                    // // 仮会員設定が有効な場合は、確認メールを送信し完了画面表示.
                    // if ($activateFlg) {
                    //     $activateUrl = $this->generateUrl('entry_activate', ['secret_key' => $Customer->getSecretKey()], UrlGeneratorInterface::ABSOLUTE_URL);

                    //     // メール送信
                    //     $this->mailService->sendCustomerConfirmMail($Customer, $activateUrl);

                    //     if ($event->hasResponse()) {
                    //         return $event->getResponse();
                    //     }

                    //     log_info('仮会員登録完了画面へリダイレクト');

                    //     return $this->redirectToRoute('entry_complete');

                    // } else {
                    //     // 仮会員設定が無効な場合は、会員登録を完了させる.
                    //     $qtyInCart = $this->entryActivate($request, $Customer->getSecretKey());

                    //     // URLを変更するため完了画面にリダイレクト
                    //     return $this->redirectToRoute('entry_activate', [
                    //         'secret_key' => $Customer->getSecretKey(),
                    //         'qtyInCart' => $qtyInCart,
                    //     ]);

                    // }
            }
        }

        return [
            'form' => $form->createView(),
            'CustomerShop' => $CustomerShop,
            'oldUploadFile' => $oldUploadFile,
            'oldUploadFile2' => $oldUploadFile2,
        ];
    }
    /**
     * @Route("/entryShop/uploadFile/add", name="entry_shop_upload_file_add")
     */
    public function uploadFileAdd(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        $images = $request->files->get('entryShop');
        $allowExtensions = ['gif', 'jpg', 'jpeg', 'png'];
        $filename = null;
        if (isset($images['upload_file_file'])) {
            $image = $images['upload_file_file'];

            //ファイルフォーマット検証
            $mimeType = $image->getMimeType();
            if (0 !== strpos($mimeType, 'image')) {
                throw new UnsupportedMediaTypeHttpException();
            }

            // 拡張子
            $extension = $image->getClientOriginalExtension();
            if (!in_array(strtolower($extension), $allowExtensions)) {
                throw new UnsupportedMediaTypeHttpException();
            }

            $filename = date('mdHis').uniqid('_').'.'.$extension;
            $image->move($this->getParameter('eccube_temp_image_dir'), $filename);
        }
        $event = new EventArgs(
            [
                'images' => $images,
                'filename' => $filename,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_ENTRYSHOP_UPLOAD_FILE_ADD_COMPLETE, $event);
        $filename = $event->getArgument('filename');

        return $this->json(['filename' => $filename], 200);
    }


    /**
     * @Route("/entryShop/uploadFile/add2", name="entry_shop_upload_file_add2")
     */
    public function uploadFileAdd2(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        $images = $request->files->get('entryShop');
        $allowExtensions = ['gif', 'jpg', 'jpeg', 'png'];
        $filename = null;
        if (isset($images['upload_file_file2'])) {
            $image = $images['upload_file_file2'];

            //ファイルフォーマット検証
            $mimeType = $image->getMimeType();
            if (0 !== strpos($mimeType, 'image')) {
                throw new UnsupportedMediaTypeHttpException();
            }

            // 拡張子
            $extension = $image->getClientOriginalExtension();
            if (!in_array(strtolower($extension), $allowExtensions)) {
                throw new UnsupportedMediaTypeHttpException();
            }

            $filename = date('mdHis').uniqid('_').'.'.$extension;
            $image->move($this->getParameter('eccube_temp_image_dir'), $filename);
        }
        $event = new EventArgs(
            [
                'images' => $images,
                'filename' => $filename,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_ENTRYSHOP_UPLOAD_FILE2_ADD_COMPLETE, $event);
        $filename = $event->getArgument('filename');

        return $this->json(['filename' => $filename], 200);
    }





    /**
     * 会員登録完了画面.
     *
     * @Route("/entryShop/complete", name="entry_shop_complete")
     * @Template("EntryShop/complete.twig")
     */
    public function complete()
    {
        return [];
    }

    

}
