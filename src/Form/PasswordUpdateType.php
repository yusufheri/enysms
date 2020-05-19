<?php

namespace App\Form;

use App\Entity\PasswordUpdate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PasswordUpdateType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add("oldPassword", PasswordType::class, $this->getConfiguration("Ancien mot de passe", "Introduire l'ancien mot de passe"))
            ->add("newPassword", PasswordType::class, $this->getConfiguration("Nouveau mot de passe", "Introduire le nouveau mot de passe"))
            ->add("confirmPassword", PasswordType::class, $this->getConfiguration("Confirmer le mot de passe", "Introduire le nouveau mot de passe pour la confirmation"))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PasswordUpdate::class,
        ]);
    }
}
