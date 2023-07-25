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

namespace Plugin\ContactManagement4\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\ContactManagement4\Form\Type\Admin\ConfigType;
use Plugin\ContactManagement4\Repository\ConfigRepository;
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
     * ConfigController constructor.
     */
    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

//    /**
//     * @Route("/%eccube_admin_route%/contact_management4/config", name="contact_management4_admin_config")
//     * @Template("@ContactManagement4/admin/config.twig")
//     */
//    public function index(Request $request)
//    {
//        $Config = $this->configRepository->get();
//        $form = $this->createForm(ConfigType::class, $Config);
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//            $Config = $form->getData();
//            $this->entityManager->persist($Config);
//            $this->entityManager->flush($Config);
//            $this->addSuccess('admin.common.save_complete', 'admin');
//
//            return $this->redirectToRoute('contact_management4_admin_config');
//        }
//
//        return [
//            'form' => $form->createView(),
//        ];
//    }
}
