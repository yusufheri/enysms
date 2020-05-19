<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class CustomerController extends AbstractController
{
    /**
     * @Route("/admin/customers", name="admin_customers")
     */
    public function index(UserRepository $userRepository)
    {
        return $this->render('admin/customer/index.html.twig', [
            'customers' => $userRepository->findBy([],["firstname" => " ASC"]),
        ]);
    }
}
