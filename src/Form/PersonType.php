<?php

namespace App\Form;

use App\Entity\Group;
use App\Entity\Person;
use App\Repository\GroupRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class PersonType extends ApplicationType
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
                'multiple' => true])
            ->add('name', TextType::class, $this->getConfiguration("Nom de la personne (*)", "Tapez le nom de la personne"))
            ->add('lastname', TextType::class, $this->getConfiguration("Post nom", "Tapez le post nom", ["required" => false]))
            ->add('surname', TextType::class, $this->getConfiguration("Prénom", "Tapez le prénom", ["required" => false]))
            ->add('phoneMain', TelType::class, $this->getConfiguration("Numéro de téléphone (*)", "Tapez le numéro de téléphone (principal)"))
            ->add('phone', TelType::class, $this->getConfiguration("Numéro de téléphone", "Tapez le numéro de téléphone", ["required" => false]))
            ->add('description', TextareaType::class, $this->getConfiguration("Description", "Tapez une petite description du  contact ", ["required" => false]))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Person::class,
        ]);
    }
}
