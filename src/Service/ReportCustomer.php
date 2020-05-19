<?php 

namespace App\Service;

use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;

class ReportCustomer{
    private $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function getMonth(){
        return date("F", mktime(0, 0, 0, date('m'), 10)); 
    }

    public function getStatsDashboard(User $user)
    {
        $totalSMS = $this->getTotalSMS($user);        
        $totalSentDaily = $this->getTotalUsedDaily($user);
        $totalBalance = $totalSMS - $this->getTotalUsed($user);

        $totalContacts = $this->getTotalContacts($user);

        $groupStatusSMSSuccess= $this->getGroupeStatusSMSSuccess($user);
        $groupStatusSMSError= $this->getGroupeStatusSMSError($user);

        $currentWeek = $this->getCurrentWeekSMS($user);

        return compact('totalBalance', 'totalSentDaily','totalContacts',
        'groupStatusSMSSuccess','groupStatusSMSError','currentWeek');
    }

    public function getStats(User $user){

        $totalSMS = $this->getTotalSMS($user);        
        $totalUsed = $this->getTotalUsed($user);
        $totalBalance = $totalSMS - $totalUsed;

        $currentMonth = $this->getMonth();
        
        $currentMonthly = $this->getCurrentMonthlySMS($user);  

        $currentMonthlyTitle = [];
        $currentSuccess = [];
        $currentError = [];

        $succes = new ArrayCollection();
        $error = new ArrayCollection();

        foreach($currentMonthly as $k => $current){
            if($current["etat"] == 1) {
                $succes->add($current);                
            } else {
                $error->add($current);                
            }
            $day = $current["day"];
            if(! in_array($day, $currentMonthlyTitle)) { $currentMonthlyTitle [] = $day;}
        }
        /* dump($currentMonthlyTitle);
        dump($succes);
        dump($error);
        die(); */


        //$currentSuccess [] = $current["note"];
        //$currentError [] = $current["note"];
        $bool1 = false ;$bool2 = false ;

        foreach($currentMonthlyTitle as $k => $day){
            foreach($succes as $k => $current){
                if ($day == $current["day"]){
                    $currentSuccess [] = $current["note"];
                    $bool1 = true;
                }                
            }
            if(!$bool1) $currentSuccess [] = "0";
            $bool1 = false;

            foreach($error as $k => $current){
                if ($day == $current["day"]){
                    $currentError [] = $current["note"];
                    $bool2 = true;
                }                
            }
            if(!$bool2) $currentError [] = "0";
            $bool2 = false;
        }

        /* foreach($currentMonthlyTitle as $k => $day){
            foreach($error as $k => $current){
                if ($day == $current["day"]){
                    $currentError [] = $current["note"];
                    $bool2 = true;
                }                
            }
            if(!$bool2) $currentError [] = "0";
            $bool2 = false;
        } */
        
        /* dump($currentSuccess);
        dump($currentError);
        die(); */

        return compact('currentSuccess','currentError','totalSMS', 'totalBalance', 'totalUsed','currentMonthly','currentMonth','currentMonthlyTitle');
    }

    public function getGroupeStatusSMSSuccess(User $user){
        return $this->manager->createQuery(
                    'SELECT COUNT(m) AS note FROM App\Entity\Message m
                    JOIN m.favorite f
                    WHERE ((f.user = :user) AND (m.status IS NOT NULL) AND (m.sentAt = :day))')
                    ->setParameter("user", $user)
                    ->setParameter("day", date('Y-m-d'))
                    ->getSingleScalarResult()  ;
    }

    public function getGroupeStatusSMSError(User $user){
        return $this->manager->createQuery(
            'SELECT COUNT(m) AS note FROM App\Entity\Message m
            JOIN m.favorite f
            WHERE ((f.user = :user) AND (m.status IS NULL) AND (m.sentAt = :day))')
            ->setParameter("user", $user)
            ->setParameter("day", date('Y-m-d'))
            ->getSingleScalarResult()  ;
    }

    public function getGroupeSentSMS(User $user){
        $data = $this->manager->createQuery(
            'SELECT COUNT(m) as note, g.title FROM App\Entity\Message m            
            JOIN m.favorite f
            JOIN f.groupes g
            WHERE (m.status IS NOT NULL) AND (f.user = :user)
            GROUP BY g'
        )
        ->setParameter("user", $user)
        ->getResult();

        return $data;
    }

    public function getCurrentWeekSMS(User $user){
        $monday = date('Y-m-d',strtotime('monday this week'));
        $sunday = date('Y-m-d',strtotime('sunday this week'));

       
        $data =  $this->manager->createQuery(
            'SELECT COUNT(m) as note, m.sentAt as day FROM App\Entity\Message m
             JOIN m.favorite f
             WHERE (f.user = :user) AND (m.status IS NOT NULL) AND (m.sentAt BETWEEN :monday AND :sunday) 
             GROUP BY m.sentAt
             ORDER BY m.sentAt')
             ->setParameter("monday", $monday)
             ->setParameter("sunday", $sunday)
             ->setParameter("user", $user)
            ->getResult();

        return $data;
    }

    public function getTotalContacts(User $user){
        return $this->manager->createQuery( //WHERE m.createdAt >= ".$currentMonth." AND m.deletedAt = null
            'SELECT COUNT(p) FROM App\Entity\Person p 
            WHERE (p.user = :user AND p.deletedAt IS NULL) ')
            ->setParameter("user", $user)
            ->getSingleScalarResult();
    }

    public function getCurrentMonthlySMS(User $user){
        $firstday = date("Y-m-01");
        $lastday = date("Y-m-t", strtotime($firstday));

       
        $data =  $this->manager->createQuery(
            'SELECT COUNT(m) AS note, m.sentAt AS day, m.state AS etat FROM App\Entity\Message m
             JOIN m.favorite f
             WHERE (m.sentAt BETWEEN :firstday AND :lastday)  AND (f.user = :user)
             GROUP BY m.sentAt, m.state
             ORDER BY m.sentAt')
             ->setParameter("firstday", $firstday)
             ->setParameter("lastday", $lastday)
             ->setParameter("user", $user)
            ->getResult();

        return $data;
    }

    public function getCurrentMonthlySMSLast(User $user){
        $firstday = date("Y-m-01");
        $lastday = date("Y-m-t", strtotime($firstday));

       
        $data =  $this->manager->createQuery(
            "SELECT  
            (SELECT COUNT(m1) as note FROM App\Entity\Message m1 JOIN m.favorite f WHERE  m1.status IS NOT NULL) AS success,
            (SELECT COUNT(m2) as note FROM App\Entity\Message m2 WHERE m2.status IS  NULL) AS Error,
             m.sentAt as day 
            FROM App\Entity\Message m
            JOIN m.favorite f
             WHERE (m.sentAt BETWEEN :firstday AND :lastday)  AND 
              AND (f.user = :user)
             GROUP BY m.sentAt
             ORDER BY m.sentAt")
             ->setParameter("firstday", $firstday)
             ->setParameter("lastday", $lastday)
             ->setParameter("user", $user)
            ->getResult();

        return $data;
    }

    /**
     * Permet de récupérer le nombre de SMS utilisé par le User
     *
     * @return int
     */
    public function getTotalUsedDaily(user $user)
    {
        return $this->manager->createQuery(
            "SELECT COUNT(m) FROM App\Entity\Message m 
            JOIN m.favorite f
            WHERE  (m.status IS NOT NULL) AND (f.user = :user) AND (m.sentAt = :day)"
        )
        ->setParameter('user', $user)
        ->setParameter('day', date('Y-m-d'))
        ->getSingleScalarResult();
    }

    /**
     * Permet de récupérer le nombre de SMS utilisé par le User
     *
     * @return int
     */
    public function getTotalUsed(user $user)
    {
        return $this->manager->createQuery(
            "SELECT COUNT(m) FROM App\Entity\Message m 
            JOIN m.favorite f
            WHERE  (m.status IS NOT NULL) AND (f.user = :user) " 
        )
        ->setParameter('user', $user)
        ->getSingleScalarResult();
    }

    /**
     * Permet de récupérer le nombre de SMS acheté par le User
     *
     * @return int
     */
    public function getTotalSMS(user $user)
    {
        return $this->manager->createQuery(
            'SELECT SUM(p.bouquet) FROM App\Entity\Payment p 
            WHERE p.customer = :user'
        )
        ->setParameter('user', $user)
        ->getSingleScalarResult();
    }
}