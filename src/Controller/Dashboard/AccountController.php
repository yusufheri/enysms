<?php

namespace App\Controller\Dashboard;

use App\Entity\User;
use App\Form\AccountType;
use App\Entity\PasswordUpdate;
use App\Form\PasswordUpdateType;
use App\Form\ProfileType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AccountController extends AbstractController
{
    /**
     * @Route("/dashboard/account/list", name="dashboard_account_list")
     */
    public function index(UserRepository $userRepository)
    {
        return $this->render('dashboard/account/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    /**
     * Permet de connecter un utilisateur
     * 
     * @Route("/login", name="dashboard_account_login") 
     * @return Response
     */
    public function login(AuthenticationUtils $utils)
    {
        $errors = $utils->getLastAuthenticationError();
        $username = $utils->getLastUsername();
        return $this->render('dashboard/account/login.html.twig', [
            'hasErrors' => $errors !== null,
            'username' => $username
        ]);
    }

    /**
     * Permet de déconnecter un utilisateur
     * 
     * @Route("/logout", name="dashboard_account_logout")
     */
    public function logout(AuthenticationUtils $authenticationUtils)
    {
       //   dump($authenticationUtils->getdate();
       dump($authenticationUtils->getLastUsername());
    }

    /**
     * Permet de créer un nouvel utilisateur
     *
     * @Route("/dashboard/account/new", name="dashboard_account_new")
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    public function new(Request $request, EntityManagerInterface $manager, UserPasswordEncoderInterface $encoder){

        $user = new User();
        $form = $this->createForm(AccountType::class, $user);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            $hash = $encoder->encodePassword($user, $user->getHash());
            $user->setHash($hash);

            $manager->persist($user);
            $manager->flush();

            $this->addFlash(
                "success",
                '<h4 class="alert-heading">Félicitations !</h4>
                <p class="mb-0">Le nouveau compte a été créé avec succès, <a href="/dashboard/account/login">connectez-vous maintenant</a> :)</p>'
            );

            return $this->redirect($request->getUri());

        }

        return $this->render("dashboard/account/new.html.twig", [
            'form' => $form->createView()
        ]);
    }

    /**
     * Permet d'afficher le profil de l'Utilisateur
     *
     * @Route("/dashboard/account", name="account_index")
     * @param EntityManagerInterface $manager
     * @return Response
     */
    public function account(EntityManagerInterface $manager){
        return $this->render(
            "dashboard/account/account.html.twig"
        );
    }

    /**
     * Permet de modifier le profile Utilisateur
     * 
     * @Route("/dashboard/account/profile", name="account_profile")
     *
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    public function profile(Request $request, EntityManagerInterface $manager){

        $user = $this->getUser();

        if (! is_null($user)){
            $form = $this->createForm(ProfileType::class, $user);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()){
                $manager->persist($user);
                $manager->flush();

                $this->addFlash(
                    "success",
                    '<h4 class="alert-heading">Parfait !</h4>
                    <p class="mb-0">Les modifications apportées à votre profile ont été enregistrées avec succès. Allez vers <i class="fas fa-sign-out-alt"></i><a href="/dashboard">
                    Dashboard</a></p>'
                );

                return $this->redirectToRoute("account_index");
            }
        }
        return $this->render('dashboard/account/profile.html.twig',[
            'form' => $form->createView()
        ]);
    }

    /**
     * Permet de modifier le mot de passe de l'Utilisateur en cours
     * 
     * @Route("/dashboard/account/password-update", name="account_update_password")
     *
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @param UserPasswordEncoderInterface $encoder
     * @return Response
     */
    public function updatePassword(Request $request, EntityManagerInterface $manager, UserPasswordEncoderInterface $encoder){
        
        $passwordUpdate = new PasswordUpdate();
        $user = $this->getUser();

        if (! is_null($user)){
            $form = $this->createForm(PasswordUpdateType::class, $passwordUpdate);
            $form->handleRequest($request);

            if($form->isSubmitted() && $form->isValid()){

                if(!(password_verify($passwordUpdate->getOldPassword(), $user->getHash()))){
                    throw new Exception("Mot de passe incorrect", 1);
                } else {
                    $newPassword = $passwordUpdate->getNewPassword();
                    $hash = $encoder->encodePassword($user, $newPassword);

                    $user->setHash($hash);

                    $manager->persist($user);
                    $manager->flush();

                    $this->addFlash(
                        "success",
                        '<h4 class="alert-heading">Félicitations !</h4>
                        <p>Le nouveau mot de passe a été enregistré avec succès</p>'
                    );

                    return $this->redirectToRoute('account_index');                    
                }
            }
        } else {
            throw new Exception("Error Processing Request", 1);
        }

        return $this->render("dashboard/account/password-update.html.twig", [
            'form' => $form->createView()
        ]);
        
    }
}
