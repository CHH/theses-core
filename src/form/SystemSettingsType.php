<?php

namespace theses\form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

use theses\PostRepository;

class SystemSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('permalinkStrategy', 'choice', [
                'expanded' => true,
                'choices' => [
                    PostRepository::PERMALINK_DATE_TITLE => '/{year}/{month}/{day}/{title}',
                    PostRepository::PERMALINK_TITLE_ONLY => '/{title}'
                ],
            ]);
    }

    public function getName()
    {
        return "system_settings";
    }
}
