<?php

namespace App\Service;

use App\Entity\User;
use Twig\Environment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class Paginator{

    private $entityClass;
    private $limit = 10;
    private $currentPage;
    private $route;
    private $templatePath;

    private $countRows;

    private $manager;
    private $twig;

    private $user;

    public function __construct(EntityManagerInterface $manager, Environment $twig, RequestStack $request, $templatePath)
    {
        $this->route = $request->getCurrentRequest()->attributes->get('_route');
        $this->twig = $twig;
        $this->manager = $manager;
        $this->templatePath = $templatePath;
    }

    public function display(){
        return $this->twig->display($this->templatePath, [
            'page'  => $this->currentPage,
            'pages' => $this->getPages(),
            'start' => $this->getStart(),
            'route' => $this->route
        ]);
    }

    public function countTotalRows(){
        return count($this->manager->getRepository($this->entityClass)->findBy(["user" => $this->user, "deletedAt" => null]));
    }

    public function getTemplatePath(){
        return $this->templatePath;
    }

    public function setTemplatePath($templatePath){
        $this->templatePath = $templatePath;

        return $this;
    }

    public function getRoute(){
        return $this->route;
    }

    public function setRoute($route){
        $this->route = $route;

        return $this;
    }

    public function getPages(){
        $total = count($this->manager->getRepository($this->entityClass)->findBy(["user" => $this->user, "deletedAt" => null]));
        return ceil($total / $this->limit);
    }

    public function getStart(){
        return ($this->currentPage - 1) * $this->limit;
    }

    public function getData($criteria = [], $order = []){

        $offset = ($this->currentPage - 1)*$this->limit;

        $data = $this->manager->getRepository($this->entityClass)->findBy($criteria, $order, $this->limit, $offset);

        return $data;
    }

    public function getDataFromQuery($query){

        $data = $this->manager->createQuery($query)->getResult();

        return $data;
    }

    public function getPage(){
        return $this->currentPage;
    }

    public function setPage($page){
        $this->currentPage = $page;

        return $this;
    }

    public function getLimit(){
        return $this->limit;
    }

    public function setLimit($limit){
        $this->limit = $limit;

        return $this;
    }

    public function getEntityClass(){
        return $this->entityClass;
    }

    public function setEntityClass($entityClass){
        $this->entityClass = $entityClass;

        return $this;
    }

    public function setUser(User $user){
        $this->user = $user;
    }
}