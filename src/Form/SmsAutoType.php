<?php

namespace App\Form;

use App\Entity\SmsAuto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SmsAutoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('step', IntegerType::class, [
                'label' => 'Etape : <span class="text-danger">*</span>',
                'label_html' => true,
                'required' => true
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Message :',
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SmsAuto::class,
        ]);
    }
}
