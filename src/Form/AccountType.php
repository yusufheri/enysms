<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstname', TextType::class,$this->getConfiguration("Nom de l'utilisateur", "Tapez le nom de l'Utilisateur"))
            ->add('lastname', TextType::class, $this->getConfiguration("Post nom de l'Utilisateur", "Tapez le post nom de l'Utilisateur"))
            ->add('email', EmailType::class, $this->getConfiguration("Email de l'Utilisateur (*)", "Tapez l'adresse mail de l'Utilisateur"))
            ->add('hash', PasswordType::class, $this->getConfiguration("Mot de passe de l'Utilisateur", "Le mot de passe de l'Utilsateur"))
            ->add('confirmPassword', PasswordType::class, $this->getConfiguration("Mot de passe de l'Utilisateur", "Le mot de passe de l'Utilsateur"))
            ->add('description', TextareaType::class, $this->getConfiguration("Description", "Parlez-nous que vous voulez créer en quelques mots"));
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
