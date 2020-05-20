<?php

namespace App\Controller\Dashboard;

use App\Entity\Group;
use App\Entity\Person;
use App\Entity\Sender;
use App\Form\BulkType;
use App\Service\Stats;
use App\Entity\Message;
use App\Entity\Favorite;
use App\Form\SingleSMSType;
use App\Service\ReportCustomer;
use App\Repository\GroupRepository;
use App\Repository\PersonRepository;
use App\Repository\SenderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SMSController extends AbstractController
{

    /**
     * Afficher le rapport synthese de SMS
     * 
     * @Route("/dashboard/sms/report", name="dashboard_sms_report")
     *
     * @param Stats $statsService
     * @return Response
     */
    public function reportSMS(ReportCustomer $reportCustomer){
        $reportCustomer = $reportCustomer->getStats($this->getUser());

        return $this->render("dashboard/sms/report.html.twig",[
            'stats' => $reportCustomer
        ]);
    }

    /**
     * Permet d'envoyer un Single SMS
     *
     * @Route("/dashboard/sms/single", name="dashboard_single")
     * 
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    public function singleSMS(Request $request, EntityManagerInterface $manager, PersonRepository $personRepository){

        $id = $request->get('id');
        
        //dump($id);

        $single = new Favorite();
        if(!is_null($id)){
            $person = $personRepository->find($id);
            $single->setPhone($person);
        }

        $user = $this->getUser();

        //$form = $this->createForm(SingleSMSType::class, $single);

        $form = $this   ->createFormBuilder($single)
                        ->add('phone', EntityType::class,[
                            'label' => "Personne ",
                            'attr'  => [
                                'placeholder' => "Selectionnez la personne",
                            ],
                            'class' => Person::class,
                            'query_builder' => function(PersonRepository $personRepository){
                                return $personRepository->createQueryBuilder('p')
                                            ->where("p.deletedAt IS NULL")
                                            ->andWhere("p.user = :user")
                                            ->orderBy("p.name", "ASC")
                                            ->setParameter("user", $this->getUser());
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
                                            ->andWhere("s.user = :user")
                                            ->orderBy("s.title", "ASC")
                                            ->setParameter("user",$this->getUser())
                                            ;
                            },
                            'choice_label' => 'title'
                        ])
                        ->add('content', TextareaType::class,[
                            'label' => "Votre message ",
                            'attr'  => [
                                'placeholder' => "Saisir un commentaire si possible",
                            ],
                        ])->getForm();


        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()){
           
            $single->setUser($user);
            $manager->persist($single);

            $person = $single->getPhone();

            $status =  $this->send_easy_sms($person->getPhoneMain(),$single->getSender()->getTitle(),$single->getContent());
            $bool = true;
            $state = 1;

            if(strpos($status, "OK:") > -1) {
                $state = 1;
            } else {$status = null;$state = null;$bool = false;}

            //if(strpos($status, "OK:") == false) {$status = null;$bool = false;$state = null;}

            $message = new Message();
            $message->setFavorite($single)
                    ->setPerson($person)
                    ->setState($state)
                    ->setStatus($status);

            $manager->persist($message);

            $manager->flush();
            if($bool) {
                 $this->addFlash("success","Le SMS envoyé avec succès");
            } else {
                $this->addFlash("danger","Le SMS n'est pas envoyé au destinataire, prière de vérifier votre connexion");
            }
           
            $manager->flush();
            return $this->redirect($request->getUri());
        }

        return $this->render('dashboard/sms/single.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/dashboard/sms/bulk", name="dashboard_bulk_index")
     */
    public function bulkSMS(Request $request, EntityManagerInterface $manager, PersonRepository $personRepository)
    {
        $bulk = new Favorite();

        //  $form = $this->createForm(BulkType::class, $bulk);

        $form = $this->createFormBuilder($bulk)
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
                                        ->andWhere("s.user = :user")
                                        ->orderBy("s.title", "ASC")
                                        ->setParameter("user",$this->getUser())
                                        ;
            },
            'choice_label' => 'title'
        ])
        ->add('content', TextareaType::class,[
            'label' => "Votre message ",
            'attr'  => [
                'placeholder' => "Saisir un commentaire si possible",
            ],
        ])->getForm();
       

        $user = $this->getUser();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            
            $bulk->setUser($user);
            $manager->persist($bulk);
            
            /* if($bulk->getGroupe()->getId() == 0) {
                
                $people = $personRepository->findAll();
                foreach($people as $k => $person){
                    $status =  $this->send_easy_sms($person->getPhoneMain(),$bulk->getSender()->getTitle(),$bulk->getContent());
                    if(strpos($status, "OK:") == false) {$status = null;$bool = false;}

                    $message = new Message();
                    $message->setFavorite($bulk)
                            ->setPerson($person)
                            ->setStatus($status);

                    $manager->persist($message); 
                }
            } else { */
            $success = 0;$counter = 0;

            foreach($bulk->getGroupes() as $k => $groupes){
                foreach($groupes->getPeople() as $l => $person){
                    $counter ++;
                    $status =  $this->send_easy_sms($person->getPhoneMain(),$bulk->getSender()->getTitle(),$bulk->getContent());
                    
                    if(strpos($status, "OK:") > -1) {
                        $success ++; $state = 1;
                    } else {$status = null;$state = null;}

                    $message = new Message();
                    $message->setFavorite($bulk)
                            ->setPerson($person)
                            ->setState($state)
                            ->setStatus($status);
        
                    $manager->persist($message);
                }              
            }
            //}
            
             
            $this->addFlash(
                "success",
                "<h3>Le bulk SMS s'est términé. (".$success."/".$counter.") messages envoyés avec succès </h3>"
            );
            $manager->flush();
            return $this->redirect($request->getUri());
        }

        return $this->render('dashboard/sms/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    function send_easy_sms($to, $from, $message, $type=1){
        $username = "yusuyher2020";
        $password = "esm38240";
        $url = "https://www.easysendsms.com/sms/bulksms-api/bulksms-api?username=$username&password=$password&from=$from&to=$to&text=".urlencode($message)."&type=$type";
        
        $curl =  curl_init();
    
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        $result = curl_exec($curl);
        curl_close($curl); 
    
        return $result;
    }

    /**
     * Permet de supprimer une catégorie
     * 
     * @Route("/dashboard/person/{id}/delete", name="dashboard_person_delete")
     *
     * @return void
     */
    public function delete(Person $person, EntityManagerInterface $manager){
        
        $libelle = $person->getFullName();
        $person->setDeletedAt(new \DateTime());
        
        if(!empty(trim($person->getFullName()))){
            $manager->persist($person);
            $manager->flush();

            $this->addFlash(
                "success",
                " <b>".$libelle."</b> a été désactivé avec succès"
            );
        }
        return $this->redirectToRoute('dashboard_person_index');
    }
}
