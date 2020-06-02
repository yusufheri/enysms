<?php

namespace App\Controller\Dashboard;

use Osms\Osms;
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
use Doctrine\Common\Collections\ArrayCollection;
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

            $phone = $this->format_number_success($person->getPhoneMain());

            if(is_numeric($phone)) {

                $number_go = new ArrayCollection();
                $number_go->add($phone);

                //  $status = $this->send_easy_sms($number_go, $single->getSender()->getTitle(), $single->getContent());
                //$status = $this->send_sms_orange($single->getSender()->getTitle(), $number_go->first(), $single->getContent());
                //dump($status);
                //die();
                //$message  = $this->messageTwilio($single->getContent());
                $message = $single->getContent();
                $this->send_sms($number_go->first(), $single->getSender()->getTitle(),$message);
                //dump($status);
                //die();

                $bool = true; 
                $status_string = "1";

                if (strlen($status_string) < 60)
                {   $state = 1;    } 
                else { $state = 0; }

                $message = new Message();
                $message->setFavorite($single)
                        ->setPerson($person)
                        ->setState($state)
                        ->setStatus($status_string);

                $manager->persist($message);
            } else {
                $bool = false;
            }            

            $manager->flush();
            if($bool) {
                 $this->addFlash("success","Le SMS envoyé avec succès");
            } else {
                $this->addFlash("danger","Le SMS n'est pas envoyé au destinataire, prière de vérifier votre connexion");
            }
           
            
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
            
           
            $success = 0;$counter = 0;

            $phones = [];
            $errorPhonesNumbers = 0;

            

            foreach($bulk->getGroupes() as $k => $groupes){
                foreach($groupes->getPeople() as $l => $person){

                    // Premier numéro
                    if(!empty($person->getPhoneMain())){
                        $number_phone = $this->goodNumber($this->format_number_success($person->getPhoneMain()), $this->format_number_success($person->getPhone()));
                        //$number_phone =$this->format_number_success($person->getPhoneMain());

                        if(is_numeric($number_phone)){
                            //if ( in_array($number_phone, $tabSuccess)){
                                if(strlen($number_phone) == 12){
                                    $counter ++;
                                    $phones [] = $number_phone;
                                

                                    $success ++; $state = 1;$status= "OK";
                                    $message = new Message();
                                    $message->setFavorite($bulk)
                                            ->setPerson($person)
                                            ->setState($state)
                                            ->setStatus($status);
                        
                                    $manager->persist($message);
                                } else {
                                    $errorPhonesNumbers ++;
                                }
                            
                        } else {
                            $errorPhonesNumbers ++;
                        }
                        
                    }
                    
                    // Deuxième numéro
                    if(!is_null($person->getPhone())){
                        if(!empty($person->getPhone())){
                            $number_phone2 =$this->format_number_success($person->getPhone());
                            if(is_numeric($number_phone2)){
                                //if ( in_array($number_phone2, $tabSuccess)){
                                    if(strlen($number_phone) == 12){
                                        $counter ++;
                            
                                        //$phones [] = $number_phone2;
                                        
                                        $success ++; $state = 1;
                                        $message = new Message();
                                        $message->setFavorite($bulk)
                                                ->setPerson($person)
                                                ->setState($state)
                                                ->setStatus($status);
                            
                                        $manager->persist($message);
                                    } else {
                                        $errorPhonesNumbers ++;
                                    }
                                    
                                //}
                                
                            } else {
                                $errorPhonesNumbers ++;
                            }
                        }
                    } 
                   
                }              
            }
            $manager->flush();
             
            //$message  = $this->messageTwilio($bulk->getContent());
            $message = $bulk->getContent();
            
            $counter2 = 0;
            for ($i= 0; $i < count($phones); $i++){
                //if (!in_array($phones[$i], $successSent)){
                    $this->send_sms($phones[$i],$bulk->getSender()->getTitle(),$message);
                    $counter2 ++;
                //}
                
            }

            dump($counter. " nombre des messages envoyés avec succès (code : ".$counter2.")".$errorPhonesNumbers." numéros de téléphone incorrects");
            die();

            $this->addFlash(
                "success",
                '<h4>Le bulk SMS s est términé. ('.$success.'/'.$counter.') + '.$errorPhonesNumbers.' numéros de téléphone incorrects</h4>
                 '
            );
            // $request->getUri()
           
            return $this->redirect("dashboard_bulk_index");
        }

        return $this->render('dashboard/sms/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /* public function bulkSMS(Request $request, EntityManagerInterface $manager, PersonRepository $personRepository)
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
            
           
            $success = 0;$counter = 0;

            $phones = [];
            $errorPhonesNumbers = 0;

            $tabSuccess = [];

            foreach($bulk->getGroupes() as $k => $groupes){
                foreach($groupes->getPeople() as $l => $person){

                    // Premier numéro
                    if(!empty($person->getPhoneMain())){
                        $number_phone =$this->format_number_success($person->getPhoneMain());

                        if(is_numeric($number_phone)){
                            //if ( in_array($number_phone, $tabSuccess)){
                                $counter ++;
                                $phones [] = $number_phone;
                            

                                $success ++; $state = 1;$status= "OK";
                                $message = new Message();
                                $message->setFavorite($bulk)
                                        ->setPerson($person)
                                        ->setState($state)
                                        ->setStatus($status);
                    
                                $manager->persist($message);
                            //}
                            
                        } else {
                            $errorPhonesNumbers ++;
                        }
                        
                    }
                    
                    // Deuxième numéro
                    if(!is_null($person->getPhone())){
                        if(!empty($person->getPhone())){
                            $number_phone2 =$this->format_number_success($person->getPhone());
                            if(is_numeric($number_phone2)){
                                //if ( in_array($number_phone2, $tabSuccess)){
                                    $counter ++;
                            
                                    $phones [] = $number_phone2;
                                    
                                    $success ++; $state = 1;
                                    $message = new Message();
                                    $message->setFavorite($bulk)
                                            ->setPerson($person)
                                            ->setState($state)
                                            ->setStatus($status);
                        
                                    $manager->persist($message);
                                //}
                                
                            } else {
                                $errorPhonesNumbers ++;
                            }
                        }
                    }
                   
                }              
            }
            //$manager->flush();
            //}
            $k = 1; $number_go = []; $aide= 50;$numbers=""; $lisungi = 1;
            //dump(count($phones));

            $number_go = new ArrayCollection();

            //$pattern = "[^0-9]#";
            $aide = 25;
            for ($i=0; $i < count($phones); $i++) { 

                $to = $phones[$i];

                if($lisungi < $aide){
                    if($i == (count($phones)-1) ){
                        $numbers .=$to;
                    } else {
                        $numbers .=$to.",";
                    }
                   
                    $lisungi += 1;

                } else if($lisungi == $aide){
                    $numbers .=$to;
                    $lisungi = 1;
                    $number_go->add($numbers) ;
                    $numbers ="";
                }             
            }
            if(!empty($numbers)){ $number_go->add($numbers) ;}
           
            
            $urls = $this->send_easy_sms_2($number_go,$bulk->getSender()->getTitle(),$bulk->getContent());  
            $lisungi = "";
            foreach($urls as $k => $url){
                $lisungi .= '
                <div class="row" id="url_'.$k.'">
                    <div class="col-md-8"> Appuyer sur le bouton pour términer l opération </div>
                    <div class="col-md-4">
                        <a  href="'.$url.'" id="_'.$k.'" target="_blank" class="btn_url btn btn-danger text-decoration-none">
                        <i class="fas fa-check"></i> Envoyez</a>
                    </div>
                </div>';
            }
            $this->addFlash(
                "success",
                '<h4>Le bulk SMS s est términé. ('.$success.'/'.$counter.') + '.$errorPhonesNumbers.' numéros de téléphone incorrects</h4>
                 <div class="row">'. $lisungi.'</div>'
            );
            // $request->getUri()
           
            return $this->redirectToRoute("dashboard_bulk_index");
        }

        return $this->render('dashboard/sms/index.html.twig', [
            'form' => $form->createView(),
        ]);
    } */

    function send_sms_orange($sender, $to, $message){

        $config = array(
            'clientId' => 'h7LivuCMDCQWNVcSh0ywmUpdGosJ7sM3',
            'clientSecret' => 'B2AAr5oISc5L4d9b'
        );
        
        $osms = new Osms($config);
        $osms->setVerifyPeerSSL(false);
        
        // retrieve an access token
        $response = $osms->getTokenFromConsumerKey();
        dump($response);

        if (!empty($response['access_token'])) {
            $senderAddress = 'tel:+243892751408';
            $receiverAddress = 'tel:+'.$to;
            $message = $message;
            $senderName = $sender;
            //dump($sender);
            return $osms->sendSMS($senderAddress, $receiverAddress, $message, $senderName);
        } else {
           return "error";
        }
 
       
    }

    function send_easy_sms_single($to, $from, $message, $type=1){
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
    
    function send_easy_sms($number_go, $from, $message, $type=1){

        $username = "yusuyher2020";
        $password = "esm38240";

        // array of curl handles
        $multiCurl = array();
        // data to be returned
        $result = array();
        // multi handle
        $mh = curl_multi_init();

        foreach ($number_go as $i => $to) {

            $fetchURL =  "https://www.easysendsms.com/sms/bulksms-api/bulksms-api?username=$username&password=$password&from=$from&to=$to&text=".urlencode($message)."&type=$type";         
            $multiCurl[$i] = curl_init();

            curl_setopt($multiCurl[$i], CURLOPT_URL,$fetchURL);
            curl_setopt($multiCurl[$i], CURLOPT_SSL_VERIFYPEER,false);
            curl_setopt($multiCurl[$i], CURLOPT_RETURNTRANSFER,true);
            curl_setopt($multiCurl[$i], CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($multiCurl[$i], CURLOPT_TIMEOUT_MS, 200);
            
            //  curl_setopt($multiCurl[$i], CURLOPT_HEADER,false);
            //  curl_setopt($multiCurl[$i], CURLOPT_RETURNTRANSFER,true);

            curl_multi_add_handle($mh, $multiCurl[$i]); 
           
        }  
        dump($fetchURL);
        //die();
        
        $index=null;
        do {
            curl_multi_exec($mh,$index);           
        } while($index > 0);

        // get content and remove handles
        foreach($multiCurl as $k => $ch) {           
            $result[$k] = curl_multi_getcontent($ch);
            curl_multi_remove_handle($mh, $ch);           
        }
        //die();
        // close
        curl_multi_close($mh); 
        //dump($result);
        //die();
        return $result;
    }

    function send_easy_sms_2($number_go, $from, $message, $type=1){

        $username = "yusuyher2020";
        $password = "esm38240";
        
        $urls = new ArrayCollection();

        foreach ($number_go as $i => $to) {

            $fetchURL =  "https://www.easysendsms.com/sms/bulksms-api/bulksms-api?from=$from&to=$to&text=".urlencode($message)."&username=$username&password=$password&type=$type";         
           
            $urls->add($fetchURL);
        }        
        
        return $urls;
    }

    function send_sms($to, $from, $message){
        $ID = $_ENV["TWILIO_ACCOUNT_ID"];
        $token = $_ENV["TWILIO_AUTH_TOKEN"];
        $url = "https://api.twilio.com/2010-04-01/Accounts/".$ID."/Messages.json";
    
        $from = str_replace("_"," ",$from);
        $data = array (
            'From' => $from,
            'To' => $to,
            'MessagingServiceSid' => 'MGde55b0c91c515d9a80917784c12a5032',
            'Body' => $message,
        );
       
        //--data-urlencode 'To=+243892751408' \
        //--data-urlencode 'From=+12565983933' \
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

    function messageTwilio($message){
        $message = str_replace("é","e", $message);
        $message = str_replace("è","e", $message);
        $message = str_replace("ê","e", $message);
        $message = str_replace("à","a", $message);
        $message = str_replace("â","a", $message);
        $message = str_replace("û","u", $message);
        $message = str_replace("ù","u", $message);

        return $message;
    }

    function goodNumber($first, $second){
        $voda1  = "24381";
        $voda2  = "24382";

        $airtel1 = "24399";
        $airtel2 = "24397";

        $pos10 = strpos($first, $voda1);
        $pos11 = strpos($first, $voda2);

        $pos20 = strpos($second, $voda1);
        $pos21 = strpos($second, $voda2);

        $posX0 = strpos($first, $airtel1);
        $posX1 = strpos($first, $airtel1);

        $posY0 = strpos($second, $airtel2);
        $posY1 = strpos($second, $airtel2);

        if (($pos10 !== false) ||  ($pos11 !== false)) {
            return $first;
        } else if (($pos20 !== false) ||  ($pos21 !== false)){
            return $second;
        } else if (($posX0 !== false) ||  ($posX1 !== false)){
            return $first;
        } else if (($posY0 !== false) ||  ($posY1 !== false)){
            return $second;
        } else {
            return $first;
        }
    }

    //  $status =  $this->send_easy_sms($person->getPhoneMain(),$bulk->getSender()->getTitle(),$bulk->getContent());
    public function format_number_success($phone){
        $to = str_replace(" ","",$phone);

        if(strlen($to)==9){ 
            $to = "243".$to;
        } else if(strlen($to)==10){
            $to = "243".substr($to,1,9);
        }

        return $to;
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
