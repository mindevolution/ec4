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
use Eccube\Event\EventArgs;
use Eccube\Repository\CustomerRepository;
use Knp\Component\Pager\PaginatorInterface;
use Plugin\ContactManagement4\PluginEvents;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SearchCustomerController extends AbstractController
{
    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    public function __construct(
        CustomerRepository $customerRepository
    ) {
        $this->customerRepository = $customerRepository;
    }

    /**
     * 顧客情報を検索する.
     *
     * @Route("/%eccube_admin_route%/contact/search/customer/html", name="plugin_contact_search_customer_html")
     * @Route("/%eccube_admin_route%/contact/search/customer/html/page/{page_no}", requirements={"page_no" = "\d+"}, name="plugin_contact_search_customer_html_page")
     * @Template("@ContactManagement4/admin/search_customer.twig")
     *
     * @param integer $page_no
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function searchCustomerHtml(Request $request, $page_no = null, PaginatorInterface $paginator)
    {
        if ($request->isXmlHttpRequest() && $this->isTokenValid()) {
            log_debug('search customer start.');
            $page_count = $this->eccubeConfig['eccube_default_page_count'];
            $session = $this->session;

            if ('POST' === $request->getMethod()) {
                $page_no = 1;

                $searchData = [
                    'multi' => $request->get('search_word'),
                ];

                $session->set('eccube.admin.contact.customer.search', $searchData);
                $session->set('eccube.admin.contact.customer.search.page_no', $page_no);
            } else {
                $searchData = (array) $session->get('eccube.admin.contact.customer.search');
                if (is_null($page_no)) {
                    $page_no = intval($session->get('eccube.admin.contact.customer.search.page_no'));
                } else {
                    $session->set('eccube.admin.contact.customer.search.page_no', $page_no);
                }
            }

            $qb = $this->customerRepository->getQueryBuilderBySearchData($searchData);

            $event = new EventArgs(
                [
                    'qb' => $qb,
                    'data' => $searchData,
                ],
                $request
            );
            $this->eventDispatcher->dispatch(PluginEvents::ADMIN_CONTACT_EDIT_SEARCH_CUSTOMER_SEARCH, $event);

            /** @var \Knp\Component\Pager\Pagination\SlidingPagination $pagination */
            $pagination = $paginator->paginate(
                $qb,
                $page_no,
                $page_count,
                ['wrap-queries' => true]
            );

            /** @var $Customers \Eccube\Entity\Customer[] */
            $Customers = $pagination->getItems();

            if (empty($Customers)) {
                log_debug('search customer not found.');
            }

            $data = [];
            $formatName = '%s%s(%s%s)';
            foreach ($Customers as $Customer) {
                $data[] = [
                    'id' => $Customer->getId(),
                    'name' => sprintf($formatName, $Customer->getName01(), $Customer->getName02(),
                        $Customer->getKana01(),
                        $Customer->getKana02()),
                    'phone_number' => $Customer->getPhoneNumber(),
                    'email' => $Customer->getEmail(),
                ];
            }

            $event = new EventArgs(
                [
                    'data' => $data,
                    'Customers' => $pagination,
                ],
                $request
            );
            $this->eventDispatcher->dispatch(PluginEvents::ADMIN_CONTACT_EDIT_SEARCH_CUSTOMER_COMPLETE, $event);
            $data = $event->getArgument('data');

            return [
                'data' => $data,
                'pagination' => $pagination,
            ];
        }
    }

    /**
     * 顧客情報を検索する.
     *
     * @Route("/%eccube_admin_route%/contact/search/customer/id", name="plugin_contact_search_customer_by_id", methods={"POST"})
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function searchCustomerById(Request $request)
    {
        if ($request->isXmlHttpRequest() && $this->isTokenValid()) {
            log_debug('search customer by id start.');

            /** @var $Customer \Eccube\Entity\Customer */
            $Customer = $this->customerRepository
                ->find($request->get('id'));

            $event = new EventArgs(
                [
                    'Customer' => $Customer,
                ],
                $request
            );
            $this->eventDispatcher->dispatch(PluginEvents::ADMIN_CONTACT_EDIT_SEARCH_CUSTOMER_BY_ID_INITIALIZE, $event);

            if (is_null($Customer)) {
                log_debug('search customer by id not found.');

                return $this->json([], 404);
            }

            log_debug('search customer by id found.');

            $data = [
                'id' => $Customer->getId(),
                'name01' => $Customer->getName01(),
                'name02' => $Customer->getName02(),
                'kana01' => $Customer->getKana01(),
                'kana02' => $Customer->getKana02(),
                'postal_code' => $Customer->getPostalCode(),
                'pref' => is_null($Customer->getPref()) ? null : $Customer->getPref()->getId(),
                'addr01' => $Customer->getAddr01(),
                'addr02' => $Customer->getAddr02(),
                'email' => $Customer->getEmail(),
                'phone_number' => $Customer->getPhoneNumber(),
                'company_name' => $Customer->getCompanyName(),
                'admin_edit_url' => $this->generateUrl('admin_customer_edit', ['id' => $Customer->getId()]),
            ];

            $event = new EventArgs(
                [
                    'data' => $data,
                    'Customer' => $Customer,
                ],
                $request
            );
            $this->eventDispatcher->dispatch(PluginEvents::ADMIN_CONTACT_EDIT_SEARCH_CUSTOMER_BY_ID_COMPLETE, $event);
            $data = $event->getArgument('data');

            return $this->json($data);
        }
    }
}
