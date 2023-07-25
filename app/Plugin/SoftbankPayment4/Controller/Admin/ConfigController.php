<?php

namespace Plugin\SoftbankPayment4\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\SoftbankPayment4\Form\Type\Admin\ConfigType;
use Plugin\SoftbankPayment4\Repository\ConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ConfigController extends AbstractController
{
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(
        ConfigRepository $configRepository
    )
    {
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/softbank_payment4/config", name="softbank_payment4_admin_config")
     * @Template("@SoftbankPayment4/admin/config.twig")
     * 
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function index(Request $request)
    {
        $Config = $this->configRepository->get();
        $form = $this->createForm(ConfigType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();

            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);
            $this->addSuccess('登録しました。', 'admin');

            return $this->redirectToRoute('softbank_payment4_admin_config');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
