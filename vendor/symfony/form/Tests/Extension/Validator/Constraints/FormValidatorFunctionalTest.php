<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Tests\Extension\Validator\Constraints;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryBuilder;
use Symfony\Component\Form\Test\ForwardCompatTestTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Expression;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\StaticMethodLoader;
use Symfony\Component\Validator\Validation;

class FormValidatorFunctionalTest extends TestCase
{
    use ForwardCompatTestTrait;

    private $validator;
    private $formFactory;

    private function doSetUp()
    {
        $this->validator = Validation::createValidatorBuilder()
            ->setMetadataFactory(new LazyLoadingMetadataFactory(new StaticMethodLoader()))
            ->getValidator();
        $this->formFactory = (new FormFactoryBuilder())
            ->addExtension(new ValidatorExtension($this->validator))
            ->getFormFactory();
    }

    public function testDataConstraintsInvalidateFormEvenIfFieldIsNotSubmitted()
    {
        $form = $this->formFactory->create(FooType::class);
        $form->submit(['baz' => 'foobar'], false);

        $this->assertTrue($form->isSubmitted());
        $this->assertFalse($form->isValid());
        $this->assertFalse($form->get('bar')->isSubmitted());
        $this->assertCount(1, $form->get('bar')->getErrors());
    }

    public function testFieldConstraintsDoNotInvalidateFormIfFieldIsNotSubmitted()
    {
        $form = $this->formFactory->create(FooType::class);
        $form->submit(['bar' => 'foobar'], false);

        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid());
    }

    public function testFieldConstraintsInvalidateFormIfFieldIsSubmitted()
    {
        $form = $this->formFactory->create(FooType::class);
        $form->submit(['bar' => 'foobar', 'baz' => ''], false);

        $this->assertTrue($form->isSubmitted());
        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('bar')->isSubmitted());
        $this->assertTrue($form->get('bar')->isValid());
        $this->assertTrue($form->get('baz')->isSubmitted());
        $this->assertFalse($form->get('baz')->isValid());
    }

    public function testNonCompositeConstraintValidatedOnce()
    {
        $form = $this->formFactory->create(TextType::class, null, [
                'constraints' => [new NotBlank(['groups' => ['foo', 'bar']])],
                'validation_groups' => ['foo', 'bar'],
            ]);
        $form->submit('');

        $violations = $this->validator->validate($form);

        $this->assertCount(1, $violations);
        $this->assertSame('This value should not be blank.', $violations[0]->getMessage());
        $this->assertSame('data', $violations[0]->getPropertyPath());
    }

    public function testCompositeConstraintValidatedInEachGroup()
    {
        $form = $this->formFactory->create(FormType::class, null, [
            'constraints' => [
                new Collection([
                    'field1' => new NotBlank([
                        'groups' => ['field1'],
                    ]),
                    'field2' => new NotBlank([
                        'groups' => ['field2'],
                    ]),
                ]),
            ],
            'validation_groups' => ['field1', 'field2'],
        ]);
        $form->add('field1');
        $form->add('field2');
        $form->submit([
            'field1' => '',
            'field2' => '',
        ]);

        $violations = $this->validator->validate($form);

        $this->assertCount(2, $violations);
        $this->assertSame('This value should not be blank.', $violations[0]->getMessage());
        $this->assertSame('data[field1]', $violations[0]->getPropertyPath());
        $this->assertSame('This value should not be blank.', $violations[1]->getMessage());
        $this->assertSame('data[field2]', $violations[1]->getPropertyPath());
    }

    public function testCompositeConstraintValidatedInSequence()
    {
        $form = $this->formFactory->create(FormType::class, null, [
            'constraints' => [
                new Collection([
                    'field1' => new NotBlank([
                        'groups' => ['field1'],
                    ]),
                    'field2' => new NotBlank([
                        'groups' => ['field2'],
                    ]),
                ]),
            ],
            'validation_groups' => new GroupSequence(['field1', 'field2']),
        ]);
        $form->add('field1');
        $form->add('field2');

        $form->submit([
            'field1' => '',
            'field2' => '',
        ]);

        $violations = $this->validator->validate($form);

        $this->assertCount(1, $violations);
        $this->assertSame('This value should not be blank.', $violations[0]->getMessage());
        $this->assertSame('data[field1]', $violations[0]->getPropertyPath());
    }

    public function testFieldsValidateInSequence()
    {
        $form = $this->formFactory->create(FormType::class, null, [
            'validation_groups' => new GroupSequence(['group1', 'group2']),
        ])
            ->add('foo', TextType::class, [
                'constraints' => [new Length(['min' => 10, 'groups' => ['group1']])],
            ])
            ->add('bar', TextType::class, [
                'constraints' => [new NotBlank(['groups' => ['group2']])],
            ])
        ;

        $form->submit(['foo' => 'invalid', 'bar' => null]);

        $errors = $form->getErrors(true);

        $this->assertCount(1, $errors);
        $this->assertInstanceOf(Length::class, $errors[0]->getCause()->getConstraint());
    }

    public function testFieldsValidateInSequenceWithNestedGroupsArray()
    {
        $form = $this->formFactory->create(FormType::class, null, [
            'validation_groups' => new GroupSequence([['group1', 'group2'], 'group3']),
        ])
            ->add('foo', TextType::class, [
                'constraints' => [new Length(['min' => 10, 'groups' => ['group1']])],
            ])
            ->add('bar', TextType::class, [
                'constraints' => [new Length(['min' => 10, 'groups' => ['group2']])],
            ])
            ->add('baz', TextType::class, [
                'constraints' => [new NotBlank(['groups' => ['group3']])],
            ])
        ;

        $form->submit(['foo' => 'invalid', 'bar' => 'invalid', 'baz' => null]);

        $errors = $form->getErrors(true);

        $this->assertCount(2, $errors);
        $this->assertInstanceOf(Length::class, $errors[0]->getCause()->getConstraint());
        $this->assertInstanceOf(Length::class, $errors[1]->getCause()->getConstraint());
    }

    public function testConstraintsInDifferentGroupsOnSingleField()
    {
        $form = $this->formFactory->create(FormType::class, null, [
            'validation_groups' => new GroupSequence(['group1', 'group2']),
        ])
            ->add('foo', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'groups' => ['group1'],
                    ]),
                    new Length([
                        'groups' => ['group2'],
                        'max' => 3,
                    ]),
                ],
            ]);
        $form->submit([
            'foo' => 'test@example.com',
        ]);

        $errors = $form->getErrors(true);

        $this->assertFalse($form->isValid());
        $this->assertCount(1, $errors);
        $this->assertInstanceOf(Length::class, $errors[0]->getCause()->getConstraint());
    }

    public function testCascadeValidationToChildFormsUsingPropertyPaths()
    {
        $form = $this->formFactory->create(FormType::class, null, [
            'validation_groups' => ['group1', 'group2'],
        ])
            ->add('field1', null, [
                'constraints' => [new NotBlank(['groups' => 'group1'])],
                'property_path' => '[foo]',
            ])
            ->add('field2', null, [
                'constraints' => [new NotBlank(['groups' => 'group2'])],
                'property_path' => '[bar]',
            ])
        ;

        $form->submit([
            'field1' => '',
            'field2' => '',
        ]);

        $violations = $this->validator->validate($form);

        $this->assertCount(2, $violations);
        $this->assertSame('This value should not be blank.', $violations[0]->getMessage());
        $this->assertSame('children[field1].data', $violations[0]->getPropertyPath());
        $this->assertSame('This value should not be blank.', $violations[1]->getMessage());
        $this->assertSame('children[field2].data', $violations[1]->getPropertyPath());
    }

    public function testCascadeValidationToChildFormsUsingPropertyPathsValidatedInSequence()
    {
        $form = $this->formFactory->create(FormType::class, null, [
            'validation_groups' => new GroupSequence(['group1', 'group2']),
        ])
            ->add('field1', null, [
                'constraints' => [new NotBlank(['groups' => 'group1'])],
                'property_path' => '[foo]',
            ])
            ->add('field2', null, [
                'constraints' => [new NotBlank(['groups' => 'group2'])],
                'property_path' => '[bar]',
            ])
        ;

        $form->submit([
            'field1' => '',
            'field2' => '',
        ]);

        $violations = $this->validator->validate($form);

        $this->assertCount(1, $violations);
        $this->assertSame('This value should not be blank.', $violations[0]->getMessage());
        $this->assertSame('children[field1].data', $violations[0]->getPropertyPath());
    }

    public function testContextIsPopulatedWithFormBeingValidated()
    {
        $form = $this->formFactory->create(FormType::class)
            ->add('field1', null, [
                'constraints' => [new Expression([
                    'expression' => '!this.getParent().get("field2").getData()',
                ])],
            ])
            ->add('field2')
        ;

        $form->submit([
            'field1' => '',
            'field2' => '',
        ]);

        $violations = $this->validator->validate($form);

        $this->assertCount(0, $violations);
    }

    public function testContextIsPopulatedWithFormBeingValidatedUsingGroupSequence()
    {
        $form = $this->formFactory->create(FormType::class, null, [
            'validation_groups' => new GroupSequence(['group1']),
        ])
            ->add('field1', null, [
                'constraints' => [new Expression([
                    'expression' => '!this.getParent().get("field2").getData()',
                    'groups' => ['group1'],
                ])],
            ])
            ->add('field2')
        ;

        $form->submit([
            'field1' => '',
            'field2' => '',
        ]);

        $violations = $this->validator->validate($form);

        $this->assertCount(0, $violations);
    }
}

class Foo
{
    public $bar;
    public $baz;

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('bar', new NotBlank());
    }
}

class FooType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('bar')
            ->add('baz', null, [
                'constraints' => [new NotBlank()],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', Foo::class);
    }
}
