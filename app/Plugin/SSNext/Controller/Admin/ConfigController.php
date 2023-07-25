<?php

namespace Plugin\SSNext\Controller\Admin;

use Doctrine\ORM\EntityManager;
use Eccube\Controller\AbstractController;
use Plugin\SSNext\Form\Type\Admin\ConfigType;
use Plugin\SSNext\Repository\ConfigRepository;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class ConfigController extends AbstractController
{

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * ConfigController constructor.
     * @param ConfigRepository $configRepository
     * @param EntityManager $entityManager
     */
    public function __construct(ConfigRepository $configRepository,
                                EntityManager $entityManager)
    {
        $this->configRepository = $configRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/%eccube_admin_route%/ss/next/config", name="ss_next_admin_config")
     * @Template("@SSNext/admin/config.twig")
     *
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function index(Request $request)
    {
        $Config = $this->configRepository->get();

        $form = $this->createForm(ConfigType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var $Config \Plugin\SSNext\Entity\Config */
            $Config = $form->getData();
            $Config->setIsProductCode(true);
            $this->entityManager->persist($Config);
            $this->entityManager->flush();

            $this->addSuccess('ss_next.admin.save.success', 'admin');
            return $this->redirectToRoute('ss_next_admin_config');
        }

        return [
            'form' => $form->createView()
        ];
    }

}