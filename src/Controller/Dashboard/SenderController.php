<?php

namespace App\Controller\Dashboard;

use App\Entity\Sender;
use App\Repository\SenderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SenderController extends AbstractController
{
    /**
     * @Route("dashboard/sender", name="dashboard_sender_index")
     */
    public function index(SenderRepository $repo)
    {
        $data = $repo->findBy(["deletedAt" => null], ["title" => "ASC"]);
       
        return $this->render('dashboard/sender/index.html.twig', [
            'data' => $data,
        ]);
    }

    
    /**
     * Permet d'enregistrer la nouvelle catégorie de membre
     *
     * @Route("/dashboard/sender/new", name="dashboard_sender_new")
     * @return Response
     */
    public function register(Request $request, EntityManagerInterface $manager){

        $libelle = $request->get('_title');
        $content = $request->get('_content');
        $user = $this->getUser();
        //die();
        $sender = new Sender();
        $sender->setTitle($libelle)
                    ->setDescription($content)
                    ->setUser($user);

        if(!empty(trim($sender->getTitle()))){
            $manager->persist($sender);
            $manager->flush();

            $this->addFlash(
                "success",
                "Le Sender :  <b>".$libelle."</b> a été enregistré avec succès"
            );
        }
        

        return $this->redirectToRoute('dashboard_sender_index');
    }

    /**
     * Permet de supprimer une catégorie
     * 
     * @Route("/dashboard/sender/{id}/delete", name="dashboard_sender_delete")
     *
     * @return void
     */
    public function delete(Sender $sender, EntityManagerInterface $manager){
        
        $libelle = $sender->getTitle();
        $sender->setDeletedAt(new \DateTime());
        
        if(!empty(trim($sender->getTitle()))){
            $manager->persist($sender);
            $manager->flush();

            $this->addFlash(
                "success",
                " <b>".$libelle."</b> a été supprimé avec succès"
            );
        }
        return $this->redirectToRoute('dashboard_sender_index');
    }
}
