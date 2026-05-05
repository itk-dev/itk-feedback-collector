<?php

namespace App\Form;

use App\Entity\Website;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WebsiteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('websiteId', TextType::class, [
                'label' => 'Name',
            ])
            ->add('url', UrlType::class, [
                'label' => 'URL',
            ])
            ->add('freescoutMailboxId', ChoiceType::class, [
                'label' => 'FreeScout Mailbox',
                'choices' => $options['mailbox_choices'],
                'required' => false,
                'placeholder' => '-- None --',
            ])
            ->add('save', SubmitType::class, [
                'label' => $options['submit_label'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Website::class,
            'mailbox_choices' => [],
            'submit_label' => 'Create Website',
        ]);
    }
}
