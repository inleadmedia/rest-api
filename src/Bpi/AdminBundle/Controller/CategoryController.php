<?php

namespace Bpi\AdminBundle\Controller;

use Bpi\ApiBundle\Domain\Entity\Category;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class CategoryController extends Controller
{
    /**
     * @return \Bpi\ApiBundle\Domain\Repository\CategoryRepository
     */
    private function getRepository()
    {
        return $this->get('doctrine.odm.mongodb.document_manager')
            ->getRepository('BpiApiBundle:Entity\Category');
    }

    /**
     * @Template
     */
    public function indexAction()
    {
        $param = $this->getRequest()->query->get('sort');
        $direction = $this->getRequest()->query->get('direction');

        $query = $this->getRepository()->listAll($param, $direction);

        $knpPaginator = $this->get('knp_paginator');

        $pagination = $knpPaginator->paginate(
            $query,
            $this->get('request')->query->get('page', 1),
            50,
            array(
                'defaultSortFieldName' => 'category',
                'defaultSortDirection' => 'asc',
            )
        );

        return array('pagination' => $pagination);
    }

    /**
     * @Template("BpiAdminBundle:Category:form.html.twig")
     */
    public function newAction()
    {
        $category = new \Bpi\ApiBundle\Domain\Entity\Category();
        $form = $this->createCategoryForm($category);
        $request = $this->getRequest();

        if ($request->isMethod('POST')) {
            $form->bind($request);
            if ($form->isValid()) {
                $this->getRepository()->save($category);
                return $this->redirect(
                    $this->generateUrl('bpi_admin_category')
                );
            }
        }

        return array(
            'form' => $form->createView(),
            'id' => null,
        );
    }

    /**
     * @Template("BpiAdminBundle:Category:form.html.twig")
     */
    public function editAction($id)
    {
        $category = $this->getRepository()->find($id);
        $form = $this->createCategoryForm($category);
        $request = $this->getRequest();

        if ($request->isMethod('POST')) {
            $form->bind($request);
            if ($form->isValid()) {
                $this->getRepository()->save($category);
                return $this->redirect(
                    $this->generateUrl('bpi_admin_category')
                );
            }
        }

        return array(
            'form' => $form->createView(),
            'id' => $id,
        );
    }

    public function disableAction($id)
    {
        $category = $this->getRepository()->find($id);

        $category->setDisabled(true);

        /** @var \Doctrine\Common\Persistence\ObjectManager $dm */
        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->flush();

        return $this->redirect(
            $this->generateUrl('bpi_admin_category')
        );
    }

    public function enableAction($id)
    {
        $category = $this->getRepository()->find($id);
        $category->setDisabled(false);

        /** @var \Doctrine\Common\Persistence\ObjectManager $dm */
        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->flush();

        return $this->redirect(
            $this->generateUrl('bpi_admin_category')
        );
    }

    private function createCategoryForm($category)
    {
        $formBuilder = $this->createFormBuilder($category)
            ->add('category', 'text')
        ;

        return $formBuilder->getForm();
    }
}
