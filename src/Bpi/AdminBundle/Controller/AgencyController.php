<?php

namespace Bpi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;

class AgencyController extends Controller
{
    /**
     * @return \Bpi\ApiBundle\Domain\Repository\AgencyRepository
     */
    private function getRepository()
    {
        return $this->get('doctrine.odm.mongodb.document_manager')
          ->getRepository('BpiApiBundle:Aggregate\Agency');
    }

    /**
     * @Template("BpiAdminBundle:Agency:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $formBuilder = $this->createFormBuilder();

        $formBuilder->add('deleted', 'choice', [
            'choices' => [
                -1 => 'All',
                1 => 'Yes',
                0 => 'No',
            ]
        ]);
        $formBuilder->add('internal', 'choice', [
            'choices' => [
                -1 => 'All',
                1 => 'Yes',
                0 => 'No',
            ]
        ]);

        $form = $formBuilder->getForm();

        $deletedFilter = null;
        $internalFilter = null;
        if ($request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {
                $agencyFilter = $form->getData();

                if (-1 !== $agencyFilter['deleted']) {
                    $deletedFilter = $agencyFilter['deleted'];
                }

                if (-1 !== $agencyFilter['internal']) {
                    $internalFilter = $agencyFilter['internal'];
                }
            }
        }

        $param = $this->getRequest()->query->get('sort');
        $direction = $this->getRequest()->query->get('direction');
        $query = $this->getRepository()->listAll($param, $direction, $deletedFilter, $internalFilter);

        $knpPaginator = $this->get('knp_paginator');

        $pagination = $knpPaginator->paginate(
            $query,
            $this->get('request')->query->get('page', 1),
            50,
            array(
                'defaultSortFieldName' => 'public_id',
                'defaultSortDirection' => 'asc',
            )
        );

        return [
            'pagination' => $pagination,
            'form' => $form->createView()
        ];
    }

    /**
     * @Template("BpiAdminBundle:Agency:form.html.twig")
     */
    public function newAction()
    {
        $agency = new \Bpi\ApiBundle\Domain\Aggregate\Agency();
        $form = $this->createAgencyForm($agency, true);
        $request = $this->getRequest();

        if ($request->isMethod('POST')) {
            $form->bind($request);
            if ($form->isValid()) {
                $this->getRepository()->save($agency);

                return $this->redirect(
                    $this->generateUrl('bpi_admin_agency')
                );
            }
        }

        return array(
            'form' => $form->createView(),
            'id' => null,
        );
    }

    /**
     * @Template("BpiAdminBundle:Agency:form.html.twig")
     */
    public function editAction($id)
    {
        $agency = $this->getRepository()->find($id);
        $form = $this->createAgencyForm($agency);
        $request = $this->getRequest();

        if ($request->isMethod('POST')) {
            $submitedAgency = $request->get('form');
            $changePublicId = $agency->getAgencyId()->id() !== $submitedAgency['publicId'];
            $submitedAgencyInternal = (isset($submitedAgency['internal'])) ? filter_var($submitedAgency['internal'], FILTER_VALIDATE_BOOLEAN) : false;
            $changeInternal = $submitedAgencyInternal !== $agency->getInternal();

            $checks = array(
                'agency_internal' => array(
                    'check' => $changeInternal,
                    'oldValue' => $agency->getInternal(),
                    'newValue' => isset($submitedAgency['internal'])
                ),
                'agency_id' => array(
                    'check' => $changePublicId,
                    'oldValue' => $agency->getAgencyId()->id(),
                    'newValue' => $submitedAgency['publicId']
                )
            );

            $changes = array();
            foreach ($checks as $key => $check) {
                if ($check['check']) {
                    $changes[$key] = array(
                        'oldValue' => $check['oldValue'],
                        'newValue' => $check['newValue']
                    );
                }
            }

            (!isset($changes['agency_id'])) ? $changes['agency_id'] = $agency->getAgencyId()->id() : false;

            $form->bind($request);
            if ($form->isValid()) {
                if ($changeInternal || $changePublicId) {
                    $facetRepository = $this->get('doctrine.odm.mongodb.document_manager')
                        ->getRepository('BpiApiBundle:Entity\Facet');
                    $facetRepository->updateFacet($changes);
                }
                $this->getRepository()->save($agency);

                return $this->redirect(
                    $this->generateUrl('bpi_admin_agency')
                );
            }
        }

        return array(
            'form' => $form->createView(),
            'id' => $id,
        );
    }

    /**
     * @Template("BpiAdminBundle:Agency:details.html.twig")
     */
    public function detailsAction($id)
    {
        $agency = $this->getRepository()->find($id);

        return array(
            'agency' => $agency
        );
    }

    public function deleteAction($id)
    {
        $this->getRepository()->delete($id);

        return $this->redirect(
            $this->generateUrl("bpi_admin_agency", array())
        );
    }

    public function purgeAction($id)
    {
        $this->getRepository()->purge($id);

        return $this->redirect(
            $this->generateUrl("bpi_admin_agency", array())
        );
    }

    public function restoreAction($id)
    {
        $this->getRepository()->restore($id);

        return $this->redirect(
            $this->generateUrl("bpi_admin_agency", array())
        );
    }

    private function createAgencyForm($agency, $new = false)
    {
        $formBuilder = $this->createFormBuilder($agency)
          ->add('publicId', 'text', array('label' => 'Public ID'))
          ->add('name', 'text')
          ->add('moderator', 'text')
          ->add('internal', 'checkbox', array(
              'label' => 'Internal',
              'value' => 1,
          ))
          ->add('publicKey', 'text')
          ->add('secret', 'text');

        if (!$new) {
            $formBuilder->add('deleted', 'checkbox', array('required' => false));
        }

        return $formBuilder->getForm();
    }
}
