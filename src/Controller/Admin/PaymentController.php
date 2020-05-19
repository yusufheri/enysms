<?php

namespace App\Controller\Admin;

use App\Entity\Balance;
use App\Entity\Payment;
use App\Form\PaymentType;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PaymentController extends AbstractController
{
    /**
     * @Route("/admin/payment", name="payment_index")
     */
    public function index(PaymentRepository $paymentRepository)
    {
        return $this->render('admin/payment/index.html.twig', [
            'payments' => $paymentRepository->findAll(),
        ]);
    }

    /**
     * @Route("/admin/payment/new", name="payment_new")
     */
    public function new(Request $request, EntityManagerInterface $manager)
    {
        $payment = new Payment();

        $form = $this->createForm(PaymentType::class, $payment);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $user = $this->getUser();
            $customer = $payment->getCustomer();

            $balanceCustomer = $customer->getBalances()->last(); 
                       
            $val = (!$balanceCustomer)?0:$balanceCustomer->getCumul();

            $payment->setUser($user);
            $manager->persist($payment);

            $balance = new Balance();
            $balance->setCreatedAt(new \DateTime())
                    ->setBalance($payment->getBouquet())
                    ->setCumul($val + $payment->getBouquet())
                    ->setUser($customer);

            $manager->persist($balance);
                    
            $manager->flush();

            $this->addFlash(
                "success",
                '<h4 class="alert-heading">Félicitations !</h4>
                <p>Le nouveau paiment a été enregistré avec succès dans la base de données</p>'
            );
            return $this->redirectToRoute("payment_index");
        }

        return $this->render('admin/payment/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
