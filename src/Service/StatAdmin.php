<?php 

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class StatAdmin {

    private $manager;

    public function __construct(EntityManagerInterface $entityManagerInterface)
    {
    $this->manager = $entityManagerInterface; 
    }

    public function getStats(){
        $users = $this->getCountUsers();
        $sms = $this->getTotalSMSBuy();
        $payment = $this->getSumPayment();

        return compact('users', 'sms', 'payment');
    }

    public function getCountUsers(){
        return $this->manager->createQuery('
                        SELECT COUNT(u) FROM App\Entity\User u WHERE u.enabled IS NULL or u.enabled = :bool
                    ')
                    ->setParameter("bool", true)
                    ->getSingleScalarResult()
        ;
    }

    public function getSumPayment()
    {
        return $this    ->manager
                        ->createQuery(
                            'SELECT SUM(p.amount) AS somme, c.title AS devise FROM App\Entity\Payment p
                            JOIN p.currency c
                            GROUP BY c')
                        ->getResult();
        
    }

    public function getTotalSMSBuy()
    {
        return $this->manager
                    ->createQuery('SELECT SUM(p.bouquet) FROM App\Entity\Payment p')
                    ->getSingleScalarResult();
    }


}