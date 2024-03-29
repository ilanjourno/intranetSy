<?php

namespace App\Form;

use App\Entity\CustomerFiles;
use App\Entity\CustomerFilesStatut;
use App\Entity\GlobalStatut;
use App\Repository\CustomerFilesStatutRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class StatusTransferType extends AbstractType
{
    public $global;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->global = $options['data']['global'];

        $builder
            ->add('customer_files_statut', EntityType::class, [
                "class" => CustomerFilesStatut::class,
                'query_builder' => function (CustomerFilesStatutRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.global_statut = :g')
                        ->setParameter('g', $this->global);
                },
                'label' => "Choisir le statut : <span class='text-danger'>*</span>",
                "label_html" => true
            ])
            ->add('customer_files', HiddenType::class);
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        ]);
    }
}
