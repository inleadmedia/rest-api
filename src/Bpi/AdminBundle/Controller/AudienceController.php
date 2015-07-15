<?php

namespace Bpi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class AudienceController extends Controller
{
    /**
     * @return \Bpi\ApiBundle\Domain\Repository\AudienceRepository
     */
    private function getRepository()
    {
        return $this->get('doctrine.odm.mongodb.document_manager')
            ->getRepository('BpiApiBundle:Entity\Audience');
    }

    /**
     * @Template
     */
    public function indexAction()
    {
       $repo = $this->getRepository();
        $query = $repo->createQueryBuilder();

        $param = $this->getRequest()->query->get('sort');
        $direction = $this->getRequest()->query->get('direction');
        if ($param && $direction) {
            $query->sort($param, $direction);
        }

        $knpPaginator = $this->get('knp_paginator');

        $pagination = $knpPaginator->paginate(
            $query,
            $this->get('request')->query->get('page', 1),
            50,
            array(
                'defaultSortFieldName' => 'audience',
                'defaultSortDirection' => 'asc',
            )
        );


        return array('pagination' => $pagination);
    }

    /**
     * @Template("BpiAdminBundle:Audience:form.html.twig")
     */
    public function newAction()
    {
        $audience = new \Bpi\ApiBundle\Domain\Entity\Audience();
        $form = $this->createAudienceForm($audience, true);
        $request = $this->getRequest();

        if ($request->isMethod('POST')) {
            $form->bind($request);
            if ($form->isValid()) {
                $this->getRepository()->save($audience);
                return $this->redirect(
                    $this->generateUrl('bpi_admin_audience')
                );
            }
        }

        return array(
            'form' => $form->createView(),
            'id' => null,
        );
    }

    /**
     * @Template("BpiAdminBundle:Audience:form.html.twig")
     */
    public function editAction($id)
    {
        $audience = $this->getRepository()->find($id);
        $form = $this->createAudienceForm($audience);
        $request = $this->getRequest();

        if ($request->isMethod('POST')) {
            $form->bind($request);
            if ($form->isValid()) {
                $this->getRepository()->save($audience);
                return $this->redirect(
                    $this->generateUrl('bpi_admin_audience')
                );
            }
        }

        return array(
            'form' => $form->createView(),
            'id' => $id,
        );
    }

    private function createAudienceForm($audience)
    {
        $formBuilder = $this->createFormBuilder($audience)
            ->add('audience', 'text')
        ;

        return $formBuilder->getForm();
    }
}
