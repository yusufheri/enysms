<?php

namespace App\Form;

use App\Entity\Person;
use App\Entity\Sender;
use App\Entity\Favorite;
use App\Form\ApplicationType;
use App\Repository\PersonRepository;
use App\Repository\SenderRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class SingleSMSType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('phone', EntityType::class,[
                'label' => "Personne ",
                'attr'  => [
                    'placeholder' => "Selectionnez la personne",
                ],
                'class' => Person::class,
                'query_builder' => function(PersonRepository $personRepository){
                    return $personRepository->createQueryBuilder('p')
                                ->where("p.deletedAt IS NULL")
                                ->orderBy("p.name", "ASC");
                },
                'choice_label' => function($person){
                    return $person->getFullNames();
                }
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
