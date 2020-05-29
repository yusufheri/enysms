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
                $message  = $this->messageTwilio($single->getContent());
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

            $tabSuccess = [];

            foreach($bulk->getGroupes() as $k => $groupes){
                foreach($groupes->getPeople() as $l => $person){

                    // Premier numéro
                    if(!empty($person->getPhoneMain())){
                        $number_phone =$this->format_number_success($person->getPhoneMain());

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
                    /* if(!is_null($person->getPhone())){
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
                    } */
                   
                }              
            }
            //$manager->flush();

            $successSent = [
                "243814093998"
, "243817408190"
, "243819601527"
, "243811609505"
, "243990929588"
, "243816033194"
, "243810331127"
, "243998510007"
, "243818225974"
, "243816968511"
, "243999903108"
, "243816853830"
, "243817576908"
, "243858268616"
, "243815191642"
, "243992492654"
, "243999939259"
, "243998677832"
, "243819311174"
, "243991177771"
, "243998623919"
, "243817800068"
, "243814037080"
, "243995768001"
, "243998289145"
, "243817006972"
, "243819094398"
, "243825002212"
, "243971051673"
, "243816561466"
, "243813604896"
, "243819339700"
, "243810021627"
, "243840483001"
, "82722735"
, "243819847834"
, "243843971300"
, "243814564292"
, "822897219990903331"
, "243822713797"
, "243814379963"
, "243818207777"
, "243814940652"
, "243999939092"
, "243818142843"
, "243815121677"
, "243811926081"
, "243994248005"
, "243812368695"
, "243844717264"
, "243820007475"
, "243999948460"
, "243991462756"
, "243815841431"
, "243810701875"
, "243998623642"
, "243998765276"
, "243815050064"
, "243812643206"
, "243824148162"
, "243990474747"
, "243815346637"
, "243859142261"
, "243995786209"
, "243999998087"
, "243995642417"
, "243815618216"
, "243998533342"
, "243990903139"
, "243821555545"
, "243855257950"
, "243810787007"
, "243810506437"
, "243822187980"
, "243818484846"
, "243822544325"
, "243814106391"
, "243810061438"
, "243819302773"
, "243819577985"
, "243816800101"
, "243998317749"
, "243818052115"
, "243812920972"
, "243828840360"
, "243819854460"
, "243997734719"
, "243851049310"
, "243891030676"
, "243815491958"
, "243815010046"
, "243854161818"
, "243813833000"
, "243820742999"
, "243812779055"
, "243818888827"
, "243999923379"
, "243810861948"
, "243812956789"
, "243823767770"
, "243814444258"
, "243818000006"
, "243856113620"
, "243812364508"
, "243819234747"
, "243999373648"
, "243810009913"
, "243812178241"
, "243818077000"
, "243810354816"
, "243817000222"
, "243990903153"
, "243844078414"
, "243815099793"
, "243993398655"
, "243994231702"
, "243974347144"
, "243815454414"
, "243818837283"
, "243816250557"
, "243827175123"
, "243972244156"
, "243813885006"
, "243994052699"
, "243994058290"
, "243994449271"
, "243976519125"
, "243843689585"
, "243990902352"
, "243819377946"
, "243852550801"
, "243999905523"
, "243997158994"
, "243824247103"
, "243997033829"
, "243999447319"
, "243815009776"
, "243811418689"
, "243826850195"
, "243975551314"
, "243819632257"
, "243997715300"
, "243817126764"
, "243970631700"
, "243818205015"
, "243814850573"
, "243997239599"
, "243813180247"
, "243997917930"
, "243998611325"
, "243994963916"
, "243825001266"
, "243911174747"
, "243998091570"
, "243998463253"
, "243998911016"
, "243814444649"
, "243840265857"
, "243825001510"
, "243995730710"
, "994799070821340200"
, "243815493290"
, "243815035611"
, "243995643128"
, "243819915854"
, "243813890333"
, "243813724706"
, "243810756581"
, "243994758080"
, "243847217111"
, "243812111211"
, "243997017142"
, "243970010969"
, "243997014821"
, "243858823474"
, "243972183470"
, "243825001577"
, "243825001211"
, "243859055653"
, "243811866777"
, "243991866939"
, "243815747497"
, "243990903357"
, "243999970091"
, "243819175081"
, "243815048876"
, "243995653598"
, "243811777440"
, "243842625622"
, "243828278005"
, "243822227132"
, "243998395950"
, "243819802786"
, "243816963136"
, "243825438135"
, "243818101774"
, "243813774838"
, "243810493000"
, "243825001175"
, "243815108852"
, "243848415862"
, "243816537766"
, "243990903608"
, "243816000302"
, "243822944654"
, "243811690871"
, "243990903190"
, "243818114767"
, "243819061200"
, "243990903361"
, "243810681510"
, "243998278598"
, "243813685910"
, "243992085274"
, "243822020699"
, "243810722670"
, "243996833651"
, "243810036477"
, "243999959347"
, "243998666034"
, "243812143033"
, "243999984043"
, "243999942015"
, "243810785503"
, "243990903361"
            ];
             
            $message  = $this->messageTwilio($bulk->getContent());
            
            $counter = 0;
            for ($i= 0; $i < count($phones); $i++){
                if (!in_array($phones[$i], $successSent)){
                    // $this->send_sms($phones[$i],$bulk->getSender()->getTitle(),$message);
                    $counter ++;
                    dump($phones[$i]);
                }
                
            }

            dump($counter);
            die();

            $this->addFlash(
                "success",
                '<h4>Le bulk SMS s est términé. ('.$success.'/'.$counter.') + '.$errorPhonesNumbers.' numéros de téléphone incorrects</h4>
                 '
            );
            // $request->getUri()
           
            return $this->redirectToRoute("dashboard_bulk_index");
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
        $ID = 'AC1d0fcb1876b51d7c2330423969e238b2';
        //$token = 'b01c1e2d870106352bee9437af318940';
        $token = 'e14a17a121a7ae1b8aacc39c4caf8fba';
        $url = "https://api.twilio.com/2010-04-01/Accounts/AC1d0fcb1876b51d7c2330423969e238b2/Messages.json";
    
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
