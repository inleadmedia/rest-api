<?php

namespace Bpi\AdminBundle\Controller;

use Bpi\ApiBundle\Domain\Aggregate\Agency;
use Bpi\ApiBundle\Domain\Entity\Facet;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route(path="/agency")
 */
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
     * @Route(path="/", name="bpi_admin_agency")
     * @Template("BpiAdminBundle:Agency:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $param = $request->query->get('sort');
        $direction = $request->query->get('direction');
        $query = $this
            ->getRepository()
            ->listAll($param, $direction);

        $knpPaginator = $this->get('knp_paginator');

        $pagination = $knpPaginator->paginate(
            $query,
            $request->query->get('page', 1),
            50,
            [
                'defaultSortFieldName' => 'public_id',
                'defaultSortDirection' => 'asc',
            ]
        );

        return ['pagination' => $pagination];
    }

    /**
     * @Route(path="/deleted", name="bpi_admin_agency_deleted")
     * @Template("BpiAdminBundle:Agency:index.html.twig")
     */
    public function deletedAction(Request $request)
    {
        $query = $this->getRepository()->findBy(
            [
                'deleted' => true,
            ]
        );

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1),
            5
        );

        return [
            'pagination' => $pagination,
            'delete_lable' => 'Undelete',
            'delete_url' => 'bpi_admin_agency_restore',
            'purge' => 1,
        ];
    }

    /**
     * @Route(path="/new", name="bpi_admin_agency_new")
     * @Template("BpiAdminBundle:Agency:form.html.twig")
     */
    public function newAction(Request $request)
    {
        $agency = new Agency();
        $form = $this->createAgencyForm($agency, true);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->getRepository()->save($agency);

                return $this->redirect(
                    $this->generateUrl('bpi_admin_agency')
                );
            }
        }

        return [
            'form' => $form->createView(),
            'id' => null,
        ];
    }

    /**
     * @Route(path="/edit/{id}", name="bpi_admin_agency_edit")
     * @Template("BpiAdminBundle:Agency:form.html.twig")
     */
    public function editAction(Request $request, Agency $agency)
    {
        $form = $this->createAgencyForm($agency);

        if ($request->isMethod('POST')) {
            $submitedAgency = $request->get('form');
            $changePublicId = $agency->getAgencyId()->id() !== $submitedAgency['publicId'];
            $submitedAgencyInternal = (isset($submitedAgency['internal'])) ? filter_var($submitedAgency['internal'], FILTER_VALIDATE_BOOLEAN) : false;
            $changeInternal = $submitedAgencyInternal !== $agency->getInternal();

            $checks = [
                'agency_internal' => [
                    'check' => $changeInternal,
                    'oldValue' => $agency->getInternal(),
                    'newValue' => isset($submitedAgency['internal']),
                ],
                'agency_id' => [
                    'check' => $changePublicId,
                    'oldValue' => $agency->getAgencyId()->id(),
                    'newValue' => $submitedAgency['publicId'],
                ],
            ];

            $changes = [];
            foreach ($checks as $key => $check) {
                if ($check['check']) {
                    $changes[$key] = [
                        'oldValue' => $check['oldValue'],
                        'newValue' => $check['newValue'],
                    ];
                }
            }

            (!isset($changes['agency_id'])) ? $changes['agency_id'] = $agency->getAgencyId()->id() : false;

            $form->handleRequest($request);

            if ($form->isValid()) {
                if ($changeInternal || $changePublicId) {
                    $facetRepository = $this
                        ->get('doctrine.odm.mongodb.document_manager')
                        ->getRepository(Facet::class);
                    $facetRepository->updateFacet($changes);
                }

                $this->getRepository()->save($agency);

                return $this->redirect(
                    $this->generateUrl('bpi_admin_agency')
                );
            }
        }

        return [
            'form' => $form->createView(),
            'id' => $agency->getId(),
        ];
    }

    /**
     * @Route(path="/details/{id}", name="bpi_admin_agency_details")
     * @Template("BpiAdminBundle:Agency:details.html.twig")
     */
    public function detailsAction(Agency $agency)
    {
        return [
            'agency' => $agency,
        ];
    }

    /**
     * @Route(path="/delete/{id}", name="bpi_admin_agency_delete")
     */
    public function deleteAction(Agency $agency)
    {
        $this->getRepository()->delete($agency->getId());

        return $this->redirect(
            $this->generateUrl("bpi_admin_agency", [])
        );
    }

    /**
     * @Route(path="/purge/{id}", name="bpi_admin_agency_purge")
     */
    public function purgeAction(Agency $agency)
    {
        $this->getRepository()->purge($agency->getId());

        return $this->redirect(
            $this->generateUrl("bpi_admin_agency_deleted", [])
        );
    }

    /**
     * @Route(path="/restore/{id}", name="bpi_admin_agency_restore")
     */
    public function restoreAction(Agency $agency)
    {
        $this->getRepository()->restore($agency->getId());

        return $this->redirect(
            $this->generateUrl("bpi_admin_agency", [])
        );
    }

    private function createAgencyForm($agency, $new = false)
    {
        $formBuilder = $this->createFormBuilder($agency)
            ->add('publicId', TextType::class, ['label' => 'Public ID'])
            ->add('name', TextType::class)
            ->add('moderator', TextType::class)
            ->add(
                'internal',
                CheckboxType::class,
                [
                    'label' => 'Internal',
                    'value' => 1,
                ]
            )
            ->add('publicKey', TextType::class)
            ->add('secret', TextType::class);

        if (!$new) {
            $formBuilder->add('deleted', CheckboxType::class, ['required' => false]);
        }

        $formBuilder->add('save', SubmitType::class, ['attr' => ['class' => 'btn']]);

        return $formBuilder->getForm();
    }
}
