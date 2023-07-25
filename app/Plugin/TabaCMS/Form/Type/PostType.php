<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaCMS\Form\Type;

use Plugin\TabaCMS\Common\Constants;
use Plugin\TabaCMS\Entity\Post;
use Plugin\TabaCMS\Util\Validator;

use Eccube\Common\EccubeConfig;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;

class PostType extends AbstractType
{

    /**
     *
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     *
     * @var array
     */
    private $eccubeConfig;

    /**
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EntityManagerInterface $entityManager, EccubeConfig $eccubeConfig)
    {
        $this->entityManager = $entityManager;
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $data = $options['data'];

        $builder->add('data_key', TextType::class, array(
            'label' => 'データキー(スラッグ)',
            'required' => true,
            'help' => 'このデータを特定するためのキー値です。URLやテンプレートファイル名などで使用します。<br>小文字アルファベット、ハイフン、アンダーバーを指定できます。指定しない場合、自動で設定します。',
            'constraints' => array(
                new Assert\NotBlank(),
                new Assert\Length(array(
                    'max' => 255
                )),
                new Assert\Callback(array(
                    'callback' => array(
                        Validator::class,
                        'unique'
                    ),
                    'payload' => array(
                        'entity' => Constants::$ENTITY['POST'],
                        'column' => 'dataKey',
                        'id' => 'postId'
                    )
                )),
                new Assert\Callback(array(
                    'callback' => array(
                        Validator::class,
                        'validDataKey'
                    )
                ))
            )
        ))
        ->add('category', EntityType::class, array(
            'label' => 'カテゴリー',
            'required' => false,
            'class' => Constants::$ENTITY['CATEGORY'],
//            'property' => 'categoryName',
            'placeholder' => '選択してください',
            'empty_data' => null,
            'constraints' => array(),
            'query_builder' => function (EntityRepository $er) use ($data) {
                return $er->createQueryBuilder('c')
                    ->where('c.typeId = :typeId')
                    ->setParameter('typeId', $data['typeId'])
                    ->orderBy('c.orderNo', 'ASC');
            }
        ))
            ->add('public_div', ChoiceType::class, array(
            'label' => '公開区分',
            'required' => true,
            'constraints' => array(
                new Assert\NotBlank()
            ),
            'choices' => Post::$PUBLIC_DIV_ARRAY,
            'multiple' => false,
            'expanded' => true
        ))
            ->add('public_date',DateType::class, array(
            'label' => '公開日時',
            'input' => 'datetime',
            'required' => true,
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd HH:mm',
            // 'empty_value' => array('year' => '----','month' => '--','day' => '--','hours' => '--','minutes' => '--'),
            'constraints' => array(
                new Assert\NotBlank()
            ),
            'attr' => array(
                'autocomplete' => 'off'
            )
        ))
            ->add('content_div', ChoiceType::class, array(
            'label' => '投稿内容区分',
            'required' => true,
            'constraints' => array(
                new Assert\NotBlank()
            ),
            'choices' => Post::$CONTENT_DIV_ARRAY,
            'multiple' => false,
            'expanded' => true
        ))
            ->add('title', TextType::class, array(
            'label' => 'タイトル',
            'required' => true,
            'constraints' => array(
                new Assert\NotBlank(),
                new Assert\Length(array(
                    'max' => 255
                ))
            )
        ))
            ->add('body', TextareaType::class, array(
            'label' => '本文',
            'required' => false,
            'constraints' => array(
                new Assert\Length(array(
//                     'max' => $this->eccubeConfig['eccube_ltext_len']
                    'max' => 65535
                ))
            )
        ))
            ->add('link_url', TextType::class, array(
            'label' => 'URL',
            'required' => false,
            'constraints' => array(
                new Assert\Length(array(
                    'max' => 255
                ))
            )
        ))
            ->add('link_target', TextType::class, array(
            'label' => 'リンクターゲット',
            'help' => '_blank , _self , _top , _parent などが指定できます。',
            'required' => false,
            'constraints' => array(
                new Assert\Length(array(
                    'max' => 16
                ))
            )
        ))
            ->add('thumbnail_file',FileType::class, array(
            'label' => 'アイキャッチ画像',
            'multiple' => false,
            'required' => false,
            'mapped' => false
        ))
            ->add('thumbnail', HiddenType::class, array(
            'label' => 'アイキャッチ画像',
            'required' => true,
            'constraints' => array()
        ))
            ->add('meta_author', TextType::class, array(
            'label' => 'author',
            'required' => false,
            'constraints' => array(
                new Assert\Length(array(
                    'max' => $this->eccubeConfig['eccube_ltext_len']
                ))
            )
        ))
            ->add('meta_description', TextType::class, array(
            'label' => 'description',
            'required' => false,
            'constraints' => array(
                new Assert\Length(array(
                    'max' => $this->eccubeConfig['eccube_ltext_len']
                ))
            )
        ))
            ->add('meta_keyword', TextType::class, array(
            'label' => 'keyword',
            'required' => false,
            'constraints' => array(
                new Assert\Length(array(
                    'max' => $this->eccubeConfig['eccube_ltext_len']
                ))
            )
        ))
            ->add('meta_robots', TextType::class, array(
            'label' => 'robots',
            'required' => false,
            'constraints' => array(
                new Assert\Length(array(
                    'max' => $this->eccubeConfig['eccube_ltext_len']
                ))
            )
        ))
            ->add('meta_tags', TextareaType::class, array(
            'label' => '追加metaタグ',
            'required' => false,
            'constraints' => array(
                new Assert\Length(array(
                    'max' => $this->eccubeConfig['eccube_ltext_len']
                ))
            )
        ))
            ->add('overwrite_route', TextType::class, array(
            'label' => 'ルーティング名',
            'required' => false,
            'help' => '既存のURLを上書きすることができます。注意して設定をしてください。\n
ルーティング名はEC-CUBEのソースコードを参照するか、symfonyコマンドで確認することができます。\n
\n
(例)\n
「当サイトについて」ページを変更したい場合はルーティング名を help_about に指定し、URLを同一にするため、投稿タイプのデータキーを「help]、投稿のデータキーを「about」にする必要があります。',
            'constraints' => array(
                new Assert\Length(array(
                    'max' => 255
                )),
                new Assert\Callback(array(
                    'callback' => array(
                        Validator::class,
                        'unique'
                    ),
                    'payload' => array(
                        'entity' => Constants::$ENTITY['POST'],
                        'column' => 'overwriteRoute',
                        'id' => 'postId'
                    )
                )),
                new Assert\Callback(array(
                    'callback' => array(
                        Validator::class,
                        'validStartsWith'
                    ),
                    'payload' => array(
                        'valid_string' => 'admin'
                    )
                ))
            )
        ))
            ->add('script', TextareaType::class, array(
            'label' => 'JavaScript',
            'required' => false,
            'constraints' => array(
                new Assert\Length(array(
                    'max' => $this->eccubeConfig['eccube_ltext_len']
                ))
            )
        ))
            ->add('memo', TextareaType::class, array(
            'label' => 'メモ',
            'required' => false,
            'help' => 'メモを残すことができます。',
            'constraints' => array(
                new Assert\Length(array(
                    'max' => $this->eccubeConfig['eccube_ltext_len']
                ))
            )
        ));
    }

    /**
     *
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return Constants::PLUGIN_CODE_LC . '_post';
    }
}
