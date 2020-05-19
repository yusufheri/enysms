<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Payment;
use App\Entity\Currency;
use App\Repository\UserRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class PaymentType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('customer', EntityType::class,[
                'label' => "Customer ",
                'attr'  => [
                    'placeholder' => "Selectionnez le client",
                ],
                'class' => User::class,
                'query_builder' => function(UserRepository $user){
                    return $user->createQueryBuilder('u')
                                ->where("u.deletedAt IS NULL")
                                ->orderBy("u.firstname", "ASC")
                                ->addOrderBy("u.lastname", "ASC");
                },
                'choice_label' => function($user){
                    return $user->getFullName();
                }
            ])
            ->add('paidAt', DateType::class, 
            $this->getConfiguration("Date de paiement", "Sélectionnez la date de paiment",['widget' => 'single_text']))
            ->add('currency', EntityType::class,[
                'label' => "Devise ",
                'attr'  => [
                    'placeholder' => "Selectionnez la devise",
                ],
                'class' => Currency::class,
                'choice_label' => 'title'
            ])
            ->add('amount', MoneyType::class, $this->getConfiguration("Montant payé", "Montant payé"))
            ->add('bouquet', IntegerType::class, $this->getConfiguration("Bouquet SMS", "Bouquet SMS"))
            ->add('amount_letter_en', TextareaType::class, $this->getConfiguration("Montant en lettres", "Montant en lettres"))
            ->add('amount_letter_fr', HiddenType::class, $this->getConfiguration("","", ["required" => false]))
            ->add('content', TextareaType::class, $this->getConfiguration("Commentaire", "Faites un commentaire si possible", ["required" => false]))
                      
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Payment::class,
        ]);
    }
}
