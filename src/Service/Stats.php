<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class Stats{
    private $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function getStats(){

        /* $totalContacts = $this->getTotalContacts();
        $sentSMSDaily = $this->getSentSMSDaily();
        $totalSent = $this->getTotalSentSMS();
        $totalBalanceSMS = $this->getTotalBalanceSMS() - $totalSent; */ 
        
        $groupSentSMS= $this->getGroupeSentSMS();
        $currentWeek = $this->getCurrentWeekSMS();

        return compact('groupSentSMS','currentWeek');
    }

    

    public function getTotalContacts(){
        return $this->manager->createQuery( //WHERE m.createdAt >= ".$currentMonth." AND m.deletedAt = null
            'SELECT COUNT(p) FROM App\Entity\Person p 
            WHERE p.deletedAt IS NULL ')
            ->getSingleScalarResult();
    }

    public function getSentSMSDaily(){
        $currentday = date("Y-m-d");
        return $this->manager->createQuery( //WHERE m.createdAt >= ".$currentMonth." AND m.deletedAt = null
            'SELECT COUNT(m) FROM App\Entity\Message m 
            WHERE (m.status IS NOT NULL) AND (m.createdAt >= :current) ')
            ->setParameter('current', $currentday)
            ->getSingleScalarResult();
    }

    public function getTotalSentSMS(){
        return $this->manager->createQuery( //WHERE m.createdAt >= ".$currentMonth." AND m.deletedAt = null
            'SELECT COUNT(m) FROM App\Entity\Message m  WHERE (m.status IS NOT NULL) ')
            ->getSingleScalarResult();
    }

    public function getTotalBalanceSMS(){
        return 1000;
    }

    public function getGroupeSentSMS(){
        $data = $this->manager->createQuery(
            'SELECT COUNT(m) as note, g.title FROM App\Entity\Message m            
            JOIN m.favorite f
            JOIN f.groupe g
            WHERE (m.status IS NOT NULL)
            GROUP BY g'
        )->getResult();

        return $data;
    }

    public function getCurrentWeekSMS(){
        $monday = date('Y-m-d',strtotime('monday this week'));
        $sunday = date('Y-m-d',strtotime('sunday this week'));

       
        $data =  $this->manager->createQuery(
            'SELECT COUNT(m) as note, m.sentAt as day FROM App\Entity\Message m
             WHERE (m.status IS NOT NULL) AND (m.sentAt BETWEEN :monday AND :sunday) 
             GROUP BY m.sentAt
             ORDER BY m.sentAt')
             ->setParameter("monday", $monday)
             ->setParameter("sunday", $sunday)
            ->getResult();

        return $data;
    }

}