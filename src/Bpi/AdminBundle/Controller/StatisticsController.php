<?php

namespace Bpi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Bpi\AdminBundle\Entity\Statistics;

class StatisticsController extends Controller
{
    /**
     * @return \Bpi\ApiBundle\Domain\Repository\HistoryRepository
     */
    private function getRepository()
    {
        return $this->get('doctrine.odm.mongodb.document_manager')
            ->getRepository('BpiApiBundle:Entity\History');
    }

    /**
     * @Template("BpiAdminBundle:Statistics:index.html.twig")
     */
    public function indexAction()
    {
        /** @var \Bpi\ApiBundle\Domain\Aggregate\Agency[] $agencies */
        $agencies = $this
            ->get('doctrine.odm.mongodb.document_manager')
            ->getRepository('BpiApiBundle:Aggregate\Agency')
            ->findAll();

        $agenciesChoice = [];
        /** @var \Bpi\ApiBundle\Domain\Aggregate\Agency $agency */
        foreach ($agencies as $agency) {
            $label = "{$agency->getName()} ({$agency->getPublicId()})";
            $agenciesChoice[$label] = $agency->getPublicId();
        }

        $request = $this->getRequest();
        $statistics = null;

        $data = new Statistics();
        $form = $this->createStatisticsForm($data);

        if ($request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {
                /** @var \Bpi\ApiBundle\Domain\Repository\AgencyRepository $agencyRepository */
                $agencyRepository = $this
                    ->get('doctrine.odm.mongodb.document_manager')
                    ->getRepository('BpiApiBundle:Aggregate\Agency');
                $qb = $agencyRepository->createQueryBuilder();

                $qb->field('public_id')->in($request->get('agencies', []));

                $selectedAgencies = $qb->getQuery()->execute();

                $statistics = [];
                if ($selectedAgencies->count()) {
                    $agencyIds = [];
                    foreach ($selectedAgencies as $selectedAgency) {
                        $agencyIds[] = $selectedAgency->getPublicId();
                    }

                    $data = $form->getData();
                    $data->getDateTo()->modify('+23 hours 59 minutes');

                    $statistics = $this->getRepository()
                        ->getStatisticsByDateRangeForAgency(
                            $data->getDateFrom(),
                            $data->getDateTo(),
                            $agencyIds
                        )->getStats();
                }
            }
        }

        return array(
            'form' => $form->createView(),
            'agencies' => $agencies,
            'selected_agencies' => $request->get('agencies', []),
            'statistics'=> $statistics,
        );
    }

    private function createStatisticsForm($data)
    {
        $formBuilder = $this
            ->createFormBuilder($data)
            ->add('dateFrom', 'text', array(
                'label' => 'From',
            ))
            ->add('dateTo', 'text', array(
                'label' => 'To',
            ));

        return $formBuilder->getForm();
    }
}
