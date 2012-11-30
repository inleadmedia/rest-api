<?php

namespace Bpi\ApiBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;

use Bpi\RestMediaTypeBundle\Document;
use Bpi\ApiBundle\Transform\Presentation;
use Bpi\ApiBundle\Transform\Extractor;

/**
 * Main entry point for REST requests
 */
class RestController extends FOSRestController
{
    /**
     * Get entity repository
     *
     * @param string $name repository name
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    protected function getRepository($name)
    {
        return $this->get('doctrine.odm.mongodb.document_manager')->getRepository($name);
    }

    /**
     * Get unserialized request body
     *
     * @return Bpi\RestMediaTypeBundle\Document
     */
    protected function getDocument()
    {
        return $this->get("serializer")->deserialize(
              $this->getRequest()->getContent(),
              'Bpi\RestMediaTypeBundle\Document',
              'xml'
        );
    }

    public function testAction()
    {
        return new Response('test_action');
    }

    /**
     * Default node listing
     *
     * @Rest\Get("/node/list")
     * @Rest\View(template="BpiApiBundle:Rest:testinterface.html.twig", statusCode="200")
     */
    public function listAction()
    {
        $node_collection = $this->getRepository('BpiApiBundle:Aggregate\Node')->findLatest();

        $document = Presentation::transformMany($node_collection);
        $document->walkEntities(function($e) use ($document) {
            $e->addLink($document->createLink('self', $this->get('router')->generate('node', array('id' => $e->property('id')->getValue()), true)));
            $e->addLink($document->createLink('collection', $this->get('router')->generate('list', array(), true)));
        });

        return $document;
    }

    /**
     * List nodes by recieved nodes_query
     *
     * @Rest\Post("/node/list")
     * @Rest\View(template="BpiApiBundle:Rest:testinterface.html.twig")
     */
    public function postNodeListAction()
    {
        $extractor = new Extractor($this->getDocument());
        $node_collection = $this->getRepository('BpiApiBundle:Aggregate\Node')
            ->findByNodesQuery($extractor->extract('nodesQuery'));

        $document = Presentation::transformMany($node_collection);
        $document->walkEntities(function($e) use ($document) {
            $e->addLink($document->createLink('self', $this->get('router')->generate('node', array('id' => $e->property('id')->getValue()), true)));
            $e->addLink($document->createLink('collection', $this->get('router')->generate('list', array(), true)));
        });

        return $document;
    }

     /**
      * Display available options
      * 1. HTTP verbs
      * 2. Expected media type entities in input/output
      *
      * @Rest\Options("/node/list")
      */
    public function nodeListOptionsAction()
    {
        $options = array(
              'GET' => array(
                    'output' => array(
                          'entities' => array(
                                'node'
                          )
                    )
              ),
              'OPTIONS' => array(),
              'POST' => array(
                    'input' => array(
                          'entities' => array(
                                'nodes_query'
                          )
                    ),
                    'output' => array(
                          'entities' => array(
                                'node'
                          )
                    )
              )
        );
        $headers = array('Allow' => implode(', ', array_keys($options)));
        return $this->handleView($this->view($options, 200, $headers));
    }

    /**
     * List available media type entities
     *
     * @category test interface
     * @Rest\Get("/shema/entity/list")
     */
    public function schemaListEntitiesAction()
    {
        $response = array('list' => array('nodes_query', 'node', 'agency', 'profile', 'resource'));
        sort($response['list']);
        return $response;
    }

    /**
     * Display example of entity
     *
     * @category test interface
     * @Rest\Get("/shema/entity/{name}")
     * @Rest\View()
     */
    public function schemaEntityAction($name)
    {
        $loader = new \Bpi\ApiBundle\Tests\DoctrineFixtures\LoadNodes();
        switch ($name) {
            case 'node':
                return Presentation::transform($loader->createAlphaNode());
            break;
            case 'resource':
                return Presentation::transform($loader->createAlphaResource());
            break;
            case 'profile':
                return Presentation::transform($loader->createAlphaProfile());
            break;
            case 'agency':
                return Presentation::transform($loader->createAlphaAgency());
            break;
            case 'nodes_query':
                $doc = new Document;
                $doc->appendEntity($entity = $doc->createEntity('nodes_query'));
                $entity->addProperty($doc->createProperty('amount', 'number', 10));
                $entity->addProperty($doc->createProperty('offset', 'number', 0));
                $entity->addProperty($doc->createProperty('filter[resource.title]', 'string', ''));
                $entity->addProperty($doc->createProperty('sort[ctime]', 'string', 'desc'));
                $entity->addProperty($doc->createProperty('reduce', 'string', 'initial', 'Reduce revisions to initial or latest'));
                return $doc;
            default:
                throw new HttpException(404, 'Requested entity does not exists');
        }
    }

    /**
     * Display node item
     *
     * @Rest\Get("/node/item/{id}")
     * @Rest\View(template="BpiApiBundle:Rest:testinterface.html.twig")
     */
    public function nodeAction($id)
    {
        $_node = $this->getRepository('BpiApiBundle:Aggregate\Node')->findOneById($id);

        $document = Presentation::transform($_node);
        $node = $document->getEntity('node');
        $node->addLink($document->createLink('self', $this->get('router')->generate('node', array('id' => $node->property('id')->getValue()), true)));
        $node->addLink($document->createLink('collection', $this->get('router')->generate('list', array(), true)));

        return $document;
    }

    /**
     * Syndicate new revision of node
     *
     * @Rest\Post("/node/item/{id}")
     * @Rest\View(template="BpiApiBundle:Rest:testinterface.html.twig", statusCode="201")
     */
    public function postNodeRevisionAction($id)
    {
        $extractor = new Extractor($this->getDocument());

        $revision = $this->get('domain.push_service')->pushRevision(
            new \Bpi\ApiBundle\Domain\ValueObject\NodeId($id),
            $extractor->extract('agency.author'),
            $extractor->extract('resource')
        );

        return Presentation::transform($revision);
    }

    /**
     * Syndicate new content
     *
     * @Rest\Post("/node")
     * @Rest\View(template="BpiApiBundle:Rest:testinterface.html.twig", statusCode="201")
     */
    public function postNodeAction()
    {
        $extractor = new Extractor($this->getDocument());

        $node = $this->get('domain.push_service')->push(
            $extractor->extract('agency.author'),
            $extractor->extract('resource'),
            $extractor->extract('profile')
        );

        return Presentation::transform($node);
    }

    /**
     * For testing purposes. Echoes back sent request
     *
     * @Rest\Get("/tools/echo")
     */
    public function echoAction()
    {
        $view = $this->view($this->get('request')->getContent(), 200);
        return $this->handleView($view);
    }
}