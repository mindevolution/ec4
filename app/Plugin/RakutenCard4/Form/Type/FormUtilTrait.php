<?php

namespace Plugin\RakutenCard4\Form\Type;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

trait FormUtilTrait
{
    /**
     * @param FormInterface $form
     * @param ConstraintViolationListInterface $errors
     */
    protected function addErrorsIfExists(FormInterface $form, ConstraintViolationListInterface $errors)
    {
        if (empty($errors)) {
            return;
        }

        foreach ($errors as $error) {
            $form->addError(new FormError(
                $error->getMessage(),
                $error->getMessageTemplate(),
                $error->getParameters(),
                $error->getPlural()));
        }
    }

}
