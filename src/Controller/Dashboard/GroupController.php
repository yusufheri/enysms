<?php

namespace App\Controller\Dashboard;

use App\Entity\Group;
use App\Repository\GroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class GroupController extends AbstractController
{
    /**
     * @Route("dashboard/group", name="dashboard_group_index")
     */
    public function index(GroupRepository $repo)
    {
        $data = $repo->findBy(["deletedAt" => null, "user" => $this->getUser()], ["title" => "ASC"]);
       
        return $this->render('dashboard/group/index.html.twig', [
            'data' => $data,
        ]);
    }

    
    /**
     * Permet d'enregistrer la nouvelle catégorie de membre
     *
     * @Route("/dashboard/group/new", name="dashboard_group_new")
     * @return Response
     */
    public function register(Request $request, EntityManagerInterface $manager){

        $libelle = $request->get('_title');
        $content = $request->get('_content');
        $user = $this->getUser();
        //die();
        $group = new Group();
        $group->setTitle($libelle)
                    ->setDescription($content)
                    ->setUser($user);

        if(!empty(trim($group->getTitle()))){
            $manager->persist($group);
            $manager->flush();

            $this->addFlash(
                "success",
                "Le groupe :  <b>".$libelle."</b> a été enregistré avec succès"
            );
        }
        

        return $this->redirectToRoute('dashboard_group_index');
    }

    /**
     * Permet de supprimer une catégorie
     * 
     * @Route("/dashboard/group/{id}/delete", name="dashboard_group_delete")
     *
     * @return void
     */
    public function delete(Group $group, EntityManagerInterface $manager){
        
        $libelle = $group->getTitle();
        $group->setDeletedAt(new \DateTime());
        
        if(!empty(trim($group->getTitle()))){
            $manager->persist($group);
            $manager->flush();

            $this->addFlash(
                "success",
                " <b>".$libelle."</b> a été supprimé avec succès"
            );
        }
        return $this->redirectToRoute('dashboard_group_index');
    }
}
