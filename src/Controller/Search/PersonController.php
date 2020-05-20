<?php

namespace App\Controller\Search;

use App\Repository\PersonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PersonController extends AbstractController
{
    /**
     * @Route("/search/person", name="search_person")
     */
    public function index()
    {
        return $this->render('search/person/index.html.twig', [
            'controller_name' => 'PersonController',
        ]);
    }

    
    public function searchPerson(){        

        $form = $this->createFormBuilder(null)
                    ->add('query', TextType::class)
                    ->getForm();

        return $this->render("search/person.html.twig", [
            'form' => $form->createView()
        ]);
    }

    /**
     * Permet de répondre à la requête effectuée lors de la recherche d un membre
     *
     * @Route("dashboard/person/handleSearch/{_query?}", name="handle_request", methods={"POST", "GET"})
     * @param MemberRepository $memberRepository
     * @param String $_query
     * @return JsonResponse
     */
    public function handleSearchMember(PersonRepository $personRepository, $_query, EntityManagerInterface $manager){

        if($_query){
            $data = $manager->createQuery("
                SELECT p FROM App\Entity\Person p 
                WHERE (p.name like :query OR p.phoneMain like :query) AND (p.deletedAt IS NULL)  AND (p.user = :user)
                ORDER BY p.name ASC
            ")  ->setParameter("query", $_query."%")
                ->setParameter("user", $this->getUser())
                ->getResult(); 
             //$personRepository->findBy(["name"]);
        } else{
            $data = $personRepository->findAll();
        }

        //var_dump($data);
        

        return $this->json($data, 200, [],['groups' => 'person:read']);
        
    }
}
