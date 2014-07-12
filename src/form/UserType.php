<?php

namespace theses\form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', 'text', ['constraints' => new Assert\NotBlank])
            ->add('password', 'password')
            ->add('display_name', 'text')
            ->add('nickname', 'text', ['label' => 'What do you want to be called personally?'])
            ->add('email', 'email', ['constraints' => new Assert\Email])
            ->add('role', 'choice', [
                'choices' => [
                    'ROLE_EDITOR' => 'Editor',
                    'ROLE_ADMIN' => 'Admin',
                ],
                'expanded' => true,
                'constraints' => new Assert\Choice(['ROLE_EDITOR', 'ROLE_ADMIN'])
            ]);
    }

    public function getName()
    {
        return "user";
    }
}
