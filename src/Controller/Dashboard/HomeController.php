<?php

namespace App\Controller\Dashboard;

use App\Service\ReportCustomer;
use App\Service\Stats;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/dashboard", name="dashboard_index")
     */
    public function index(ReportCustomer $reportCustomer)
    {
        $report = $reportCustomer->getStatsDashboard($this->getUser());
        //dump($stats->getCurrentWeekSMS());
        return $this->render('dashboard/index.html.twig', [
            'report' => $report,
        ]);
    }
}
