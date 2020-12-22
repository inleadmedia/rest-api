<?php

namespace Bpi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class NodeController extends Controller
{
    /**
     * @return \Bpi\ApiBundle\Domain\Repository\NodeRepository
     */
    private function getRepository()
    {
        return $this->get('doctrine.odm.mongodb.document_manager')
            ->getRepository('BpiApiBundle:Aggregate\Node');
    }

    /**
     * @Template("BpiAdminBundle:Node:index.html.twig")
     */
    public function indexAction()
    {

        $param = $this->getRequest()->query->get('sort');
        $direction = $this->getRequest()->query->get('direction');
        $search = $this->getRequest()->query->get('search');
        $query = $this->getRepository()->listAll($param, $direction, $search);

        $knpPaginator = $this->get('knp_paginator');

        $pagination = $knpPaginator->paginate(
            $query,
            $this->get('request')->query->get('page', 1),
            50,
            array(
                'defaultSortFieldName' => 'resource.title',
                'defaultSortDirection' => 'desc',
            )
        );

        return array('pagination' => $pagination);
    }

    /**
     * Show deleted nodes
     *
     * @Template("BpiAdminBundle:Node:index.html.twig")
     */
    public function deletedAction()
    {

        $query = $this->getRepository()->listAll(null, null, null, true);
        $knpPaginator = $this->get('knp_paginator');

        $pagination = $knpPaginator->paginate(
            $query,
            $this->get('request')->query->get('page', 1),
            50,
            array(
                'defaultSortFieldName' => 'resource.title',
                'defaultSortDirection' => 'desc',
            )
        );

        return array(
            'pagination' => $pagination,
            'delete_lable' => 'Undelete',
            'delete_url' => 'bpi_admin_node_restore',
        );
    }

    /**
     * @Template("BpiAdminBundle:Node:form.html.twig")
     */
    public function newAction()
    {
        $node = new \Bpi\ApiBundle\Domain\Aggregate\Node();
        $form = $this->createNodeForm($node, true);
        $request = $this->getRequest();

        if ($request->isMethod('POST')) {
            $form->bind($request);
            if ($form->isValid()) {
                $this->getRepository()->save($node);
                return $this->redirect(
                    $this->generateUrl('bpi_admin_node')
                );
            }
        }

        return array(
            'form' => $form->createView(),
            'id' => null,
        );
    }

    /**
     * @Template("BpiAdminBundle:Node:form.html.twig")
     */
    public function editAction($id)
    {
        /** @var \Bpi\ApiBundle\Domain\Aggregate\Node $node */
        $node = $this->getRepository()->find($id);
        $form = $this->createNodeForm($node);
        $request = $this->getRequest();
        $dm = $this->get('doctrine_mongodb.odm.document_manager');

        if ($request->isMethod('POST')) {
            $submittedNode = $request->get('form');
            $changeAuthorFirstName = $node->getAuthorFirstName() != $submittedNode['authorFirstName'];
            $changeAuthorLastName = $node->getAuthorLastName() != $submittedNode['authorLastName'];
            $changeAuthor = $changeAuthorFirstName || $changeAuthorLastName;
            $changeCategory = $node->getCategory()->getId() != $submittedNode['category'];
            $changeAudience = $node->getAudience()->getId() != $submittedNode['audience'];

            $checks = array(
                'author' => array(
                    'check' => $changeAuthor,
                    'oldValue' => $node->getAuthor()->getFullName(),
                    'newValue' => ($submittedNode['authorFirstName'] ? $submittedNode['authorFirstName'] . ' ' : '') . $submittedNode['authorLastName']
                ),
                'category' => array(
                    'check' => $changeCategory,
                    'oldValue' => $node->getCategory()->getCategory(),
                    'newValue' => $dm->getRepository('BpiApiBundle:Entity\Category')->find($submittedNode['category'])->getCategory()
                ),
                'audience' => array(
                    'check' => $changeAudience,
                    'oldValue' => $node->getAudience()->getAudience(),
                    'newValue' => $dm->getRepository('BpiApiBundle:Entity\Audience')->find($submittedNode['audience'])->getAudience()
                ),
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
            $changes['nodeId'] = $node->getId();

            $form->bind($request);
            if ($form->isValid()) {
                $modifyTime = new \DateTime();
                $node->setMtime($modifyTime);
                $facetRepository = $this->get('doctrine.odm.mongodb.document_manager')
                    ->getRepository('BpiApiBundle:Entity\Facet');
                $facetRepository->updateFacet($changes);

                $this->getRepository()->save($node);
                return $this->redirect(
                    $this->generateUrl('bpi_admin_node')
                );
            }
        }

        $assets = array();
        $nodeAssets = $node->getResource()->getAssets();
        if (!empty($nodeAssets)) {
            $assets = $this->prepareAssets($nodeAssets);
        }

        return array(
            'form' => $form->createView(),
            'id' => $id,
            'assets' => $assets
        );
    }

    /**
     * @Template("BpiAdminBundle:Node:details.html.twig")
     */
    public function detailsAction($id)
    {
        $node = $this->getRepository()->find($id);

        $assets = array();
        $nodeAssets = $node->getResource()->getAssets();
        if (!empty($nodeAssets)) {
            $assets = $this->prepareAssets($nodeAssets);
        }

        return array(
            'node' => $node,
            'assets' => $assets,
        );
    }

    public function deleteAction($id)
    {
        $this->getRepository()->delete($id, 'ADMIN');
        $this->get('doctrine.odm.mongodb.document_manager')
            ->getRepository('BpiApiBundle:Entity\Facet')
            ->delete($id)
        ;
        return $this->redirect(
            $this->generateUrl("bpi_admin_node", array())
        );
    }

    public function restoreAction($id)
    {
        $this->getRepository()->restore($id, 'ADMIN');
        $node = $this->getRepository()->findOneById($id);
        $this->get('doctrine.odm.mongodb.document_manager')
          ->getRepository('BpiApiBundle:Entity\Facet')
          ->prepareFacet($node)
        ;
        return $this->redirect(
            $this->generateUrl("bpi_admin_node", array())
        );
    }

    private function createNodeForm($node, $new = false)
    {
        $formBuilder = $this->createFormBuilder($node, array('csrf_protection' => false))
            ->add(
                'authorFirstName',
                'text',
                array(
                    'label' => 'Author first name',
                    'required' => true
                )
            )
            ->add(
                'authorLastName',
                'text',
                array(
                    'label' => 'Author last name',
                    'required' => false
                )
            )
            ->add(
                'authorAgencyId',
                'text',
                array(
                    'label' => 'Author agency id',
                    'required' => true,
                    'disabled' => true
                )
            )
            ->add(
                'ctime',
                'datetime',
                array(
                    'label' => 'Creation time',
                    'required' => true,
                    'date_widget' => 'single_text',
                    'time_widget' => 'single_text',
                    'disabled' => true
                )
            )
            ->add(
                'mtime',
                'datetime',
                array(
                    'label' => 'Modify time',
                    'required' => true,
                    'date_widget' => 'single_text',
                    'time_widget' => 'single_text',
                    'disabled' => true
                )
            )
            ->add('title', 'text')
            ->add('teaser', 'textarea')->setRequired(false)
            ->add('body', 'textarea')->setRequired(false)
            ->add(
                'category',
                'document',
                array(
                    'class' => 'BpiApiBundle:Entity\Category',
                    'property' => 'category',
                )
            )
            ->add(
                'audience',
                'document',
                array(
                    'class' => 'BpiApiBundle:Entity\Audience',
                    'property' => 'audience'
                )
            );

        if (!$new) {
            $formBuilder->add('deleted', 'checkbox', array('required' => false));
        }

        return $formBuilder->getForm();
    }

    /**
     * Filter assets on images and documents
     *
     * @param $nodeAssets
     * @return array
     */
    protected function prepareAssets($nodeAssets)
    {
        $imageExtensions = array('jpg', 'jpeg', 'png', 'gif');
        $assets = array();
        foreach ($nodeAssets as $asset) {
            $asset['url'] = $this->generateUrl('get_asset', array(
                'filename' => $asset['file'],
                'extension' => $asset['extension'],
            ));
            if (in_array($asset['extension'], $imageExtensions)) {
                $assets['images'][] = $asset;
            } else {
                $assets['documents'][] = $asset;
            }
        }

        return $assets;
    }
}
