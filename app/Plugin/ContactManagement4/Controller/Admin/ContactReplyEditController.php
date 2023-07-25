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
use Plugin\ContactManagement4\Entity\Contact;
use Plugin\ContactManagement4\Entity\ContactReply;
use Plugin\ContactManagement4\Entity\MailHistory;
use Plugin\ContactManagement4\Form\Type\Admin\ContactReplyType;
use Plugin\ContactManagement4\Repository\ContactReplyRepository;
use Plugin\ContactManagement4\Repository\ContactRepository;
use Plugin\ContactManagement4\Service\MailService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ContactReplyEditController extends AbstractController
{
    /**
     * @var ContactRepository
     */
    protected $contactRepository;

    /**
     * @var ContactReplyRepository
     */
    private $contactReplyRepository;

    /**
     * @var MailService
     */
    private $mailService;

    public function __construct(
        ContactRepository $contactRepository,
        ContactReplyRepository $contactReplyRepository,
        MailService $mailService
    ) {
        $this->contactRepository = $contactRepository;
        $this->contactReplyRepository = $contactReplyRepository;
        $this->mailService = $mailService;
    }

    /**
     * @Route("/%eccube_admin_route%/contact/{contact_id}/reply/new", name="plugin_contact_reply_new")
     * @Route("/%eccube_admin_route%/contact/{contact_id}/reply/{id}/edit", requirements={"id" = "\d+"}, name="plugin_contact_reply_edit")
     * @ParamConverter("Contact", class="Plugin\ContactManagement4\Entity\Contact", options={"id" = "contact_id"})
     * @Template("@ContactManagement4/admin/reply_edit.twig")
     *
     * @param ContactReply|null $ContactReply
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function index(Request $request, Contact $Contact, $id = null)
    {
        $isEdit = false;

        // 編集
        if (!is_null($id)) {
            $isEdit = true;
            $ContactReply = $this->contactReplyRepository->find($id);

            if (!$ContactReply) {
                throw new NotFoundHttpException();
            }
        } else {
            // 新規登録
            $date = new \DateTime();
            $date->setTime(0, 0, 0, 0);
            $ContactReply = new ContactReply();
            $ContactReply
                ->setContact($Contact)
                ->setStaff($this->getUser())
                ->setReplyDate($date);

            $ContactReply->setContents(
                $Contact->getName01().' '.$Contact->getName02().' 様'."\n\n".
                preg_replace('/^.*$/mu', '＞$0', $Contact->getContents())
            );

            // 返信先アドレスを初期値セット
            $ContactReply->setEmail($Contact->getEmail());
        }

        // 会員登録フォーム
        $builder = $this->formFactory
            ->createBuilder(ContactReplyType::class, $ContactReply);

        $event = new EventArgs(
            [
                'builder' => $builder,
                'ContactReply' => $ContactReply,
            ],
            $request
        );
        $this->eventDispatcher->dispatch('admin.contact.reply_edit.index.initialize', $event);

        $form = $builder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            log_info('問い合わせ返信登録開始', [$ContactReply->getId()]);

            if (!$isEdit) {
                $ContactReply->setReplyDate(new \DateTime());
            }

            $this->entityManager->persist($ContactReply);
            $this->entityManager->flush();

            log_info('問い合わせ返信登録完了', [$ContactReply->getId()]);

            $event = new EventArgs(
                [
                    'form' => $form,
                    'CustomerReply' => $ContactReply,
                ],
                $request
            );
            $this->eventDispatcher->dispatch('admin.contact.reply_edit.index.complete', $event);

            if ($request->get('mode') === 'send') {
                $message = $this->mailService->sendReplyMail($ContactReply);
                $history = new MailHistory();
                $history
                    ->setToEmail($ContactReply->getEmail())
                    ->setSendDate(new \DateTime())
                    ->setMailSubject($message->getSubject())
                    ->setMailBody($message->getBody())
                    ->setCreator($this->getUser())
                    ->setContact($Contact)
                    ->setContactReply($ContactReply);

                $this->entityManager->persist($history);
                $this->entityManager->flush($history);

                $this->addSuccess('メールを送信しました', 'admin');
            } else {
                $this->addSuccess('admin.common.save_complete', 'admin');
            }

            return $this->redirectToRoute('plugin_contact_reply_edit', [
                'contact_id' => $Contact->getId(),
                'id' => $ContactReply->getId(),
            ]);
        }

        return [
            'form' => $form->createView(),
            'Contact' => $Contact,
            'ContactReply' => $ContactReply,
            'isEdit' => $isEdit,
        ];
    }
}
