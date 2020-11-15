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
    private $sms_message = "Cliquez sur ce lien pour que vous soyez ajouter dans notre base de donnees http://wa.me/+14155238886?text=join%20slightly-grow";
    /**
     * @Route("/dashboard/person/{page<\d+>?1}", name="dashboard_person_index")
     */
    public function index(int $page, Paginator $paginator)
    {
        //  $data = $personRepository->findBy([],["name" => "ASC"]);
        $user = $this->getUser();

        $paginator  ->setEntityClass(Person::class)
                    ->setLimit(20)
                    ->setPage($page)                    
                    ->setUser($user);

        return $this->render('dashboard/person/index.html.twig', [            
            'data' => $paginator->getData(["deletedAt" => null, "user" => $user], ["name" => "ASC", "createdAt" => "DESC"]),
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
            //dd($user);
            $pos = strpos( $user->getEmail(), "demo") ;
            //dd($pos);
            if ($pos == 0){
                $this->send_sms($person->getPhoneMain(), $this->sms_message);
                $this->send_sms($person->getPhone(), $this->sms_message);
            }
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

    function send_sms($to, $message){
        $ID = $_ENV["TWILIO_ACCOUNT_ID_2"];
        $token = $_ENV["TWILIO_AUTH_TOKEN_2"];
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/".$ID."/Messages.json";
        
        

        if(!(is_null($to))){
            if (!empty($to)){
                if (strpos($to, "+") === false) { $to ="+".$to; }
                $data = array(
                    //'From' => '+12565983933',
                    'From' => '+16624993646',
                    'To' => $to,
                    //'MessagingServiceSid' => 'MGde55b0c91c515d9a80917784c12a5032',
                    'Body' => $message,
                );
                
                $post = http_build_query($data);
                $x = curl_init($url );
                curl_setopt($x, CURLOPT_POST, true);
                curl_setopt($x, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($x, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($x, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($x, CURLOPT_USERPWD, "$ID:$token");
                curl_setopt($x, CURLOPT_POSTFIELDS, $post);
                $y = curl_exec($x);
                curl_close($x);
                return $y;
            }
        }
        
    }

    /**
     * @Route("/dashboard/person/{id}/edit", name="dashboard_person_edit")
     */
    public function edit(Request $request, EntityManagerInterface $manager, Person $person){
        
        //  $form = $this->createForm(PersonType::class, $person);

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
