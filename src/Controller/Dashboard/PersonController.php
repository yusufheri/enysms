<?php

namespace App\Controller\Dashboard;

use App\Entity\Group;
use App\Entity\Person;
use App\Form\PersonType;
use App\Service\Paginator;
use App\Repository\GroupRepository;
use App\Repository\PersonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PersonController extends AbstractController
{
    /**
     * @Route("/dashboard/person/{page<\d+>?1}", name="dashboard_person_index")
     */
    public function index(int $page, Paginator $paginator)
    {
        //  $data = $personRepository->findBy([],["name" => "ASC"]);

        $paginator  ->setEntityClass(Person::class)
                    ->setLimit(10)
                    ->setPage($page);

        return $this->render('dashboard/person/index.html.twig', [            
            'data' => $paginator->getData(["deletedAt" => null, "user" => $this->getUser()], ["name" => "ASC", "createdAt" => "DESC"]),
            'paginator' => $paginator,
        ]);
    }

    public function getConfiguration($label, $placeholder, $options = []) {
        return array_merge_recursive([
            'label' => $label,
            'attr'  => [
                'placeholder' => $placeholder,
                //  'class' => $class
            ]
        ], $options);
    }

    /**
     * @Route("/dashboard/person/new", name="dashboard_person_new")
     */
    public function new(Request $request, EntityManagerInterface $manager)
    {
        $person = new Person();

        // $form = $this->createForm(PersonType::class, $person);

        $form = $this   ->createFormBuilder($person)
                        ->add('groupes', EntityType::class,[
                            'label' => "Groupe ",
                            'attr'  => [
                                'placeholder' => "Selectionnez le groupe ou catégorie de la personne",
                            ],
                            'class' => Group::class,
                            'query_builder' => function(GroupRepository $groupRepository){
                                return $groupRepository->createQueryBuilder('g')
                                            ->where("g.deletedAt IS NULL")
                                            ->andWhere("g.user = :user")
                                            ->orderBy("g.title", "ASC")
                                            ->setParameter("user", $this->getUser());
                            },
                            'choice_label' => 'title',
                            'multiple' => true])
                        ->add('name', TextType::class, $this->getConfiguration("Nom de la personne (*)", "Tapez le nom de la personne"))
                        ->add('lastname', TextType::class, $this->getConfiguration("Post nom", "Tapez le post nom", ["required" => false]))
                        ->add('surname', TextType::class, $this->getConfiguration("Prénom", "Tapez le prénom", ["required" => false]))
                        ->add('phoneMain', TelType::class, $this->getConfiguration("Numéro de téléphone (*)", "Tapez le numéro de téléphone (principal)"))
                        ->add('phone', TelType::class, $this->getConfiguration("Numéro de téléphone", "Tapez le numéro de téléphone", ["required" => false]))
                        ->add('description', TextareaType::class, $this->getConfiguration("Description", "Tapez une petite description du  contact ", ["required" => false]))
                        ->getForm();

        $user = $this->getUser();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            $person->setUser($user);

            $manager->persist($person);
            $manager->flush();

            $this->addFlash(
                "success",
                "Nouveau contact enregisté avec succès"
            );
            return $this->redirect($request->getUri());
            // $this->redirectToRoute("dashboard_person_index");
        }

        return $this->render('dashboard/person/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/dashboard/person/{id}/edit", name="dashboard_person_edit")
     */
    public function edit(Request $request, EntityManagerInterface $manager, Person $person){
        
        $form = $this->createForm(PersonType::class, $person);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $manager->persist($person);
            $manager->flush();

            $this->addFlash(
                "success",
                "Modifications apportées au contact ".$person->getFullName()." ont été enregistrées avec succès"
            );
            return $this->redirectToRoute("dashboard_person_index");
        }

        return $this->render("dashboard/person/edit.html.twig", [
            "form" => $form->createView(),
            "person" =>$person
        ]);
    }

    /**
     * @Route("/dashboard/person/{id}/profile", name="dashboard_person_profile")
     */
    public function profile(PersonRepository $personRepository, Person $person){
        return $this->render("dashboard/person/profile.html.twig", [
            "person" => $personRepository->findOneBy(["id" => $person->getId(), "deletedAt" => null])
        ]);
    }
    
}
