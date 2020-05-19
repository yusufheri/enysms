<?php

namespace App\Form;

use App\Entity\Group;
use App\Entity\Sender;
use App\Entity\Favorite;
use App\Repository\GroupRepository;
use App\Repository\SenderRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class BulkType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('groupes', EntityType::class,[
            'label' => "Groupe ",
            'attr'  => [
                'placeholder' => "Selectionnez le groupe ou catégorie de la personne",
            ],
            'class' => Group::class,
            'query_builder' => function(GroupRepository $groupRepository){
                return $groupRepository->createQueryBuilder('g')
                            ->where("g.deletedAt IS NULL")
                            ->orderBy("g.title", "ASC");
            },
            'choice_label' => 'title',
            'multiple' => true
        ])
        ->add('sender', EntityType::class,[
            'label' => "Sender ",
            'attr'  => [
                'placeholder' => "Selectionnez le Sender",
            ],
            'class' => Sender::class,
            'query_builder' => function(SenderRepository $senderRepository){
                return $senderRepository->createQueryBuilder('s')
                            ->where("s.deletedAt IS NULL")
                            ->orderBy("s.title", "ASC");
            },
            'choice_label' => 'title'
        ])
        ->add('content', TextareaType::class,$this->getConfiguration("Votre message", "Saisir un commentaire si possible"));
             
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Favorite::class,
        ]);
    }
}
