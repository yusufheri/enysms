<?php

namespace App\Controller\Dashboard;

use App\Entity\Favorite;
use App\Repository\FavoriteRepository;
use App\Service\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class FavoriteController extends AbstractController
{
    /**
     * @Route("/dashboard/favorite/{page<\d+>?1}", name="dashboard_favorite_index")
     */
    public function index(int $page, Paginator $paginator)
    {
        $paginator  ->setEntityClass(Favorite::class)
                    ->setLimit(10)
                    ->setPage($page)
                    ->setUser($this->getUser());

        return $this->render('dashboard/favorite/index.html.twig', [
            //'favorites' => $favoriteRepository->findBy([],["createdAt" => "DESC"]),
            'data' => $paginator->getData(["deletedAt" => null, "user" => $this->getUser()], ["createdAt" => "DESC"]),
            'paginator' => $paginator,
        ]);
    }
}
