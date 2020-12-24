<?php

namespace Bpi\ApiBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints;

use Bpi\RestMediaTypeBundle\Document;
use Bpi\ApiBundle\Domain\Entity\History;
use Bpi\ApiBundle\Domain\ValueObject\NodeId;
use Bpi\ApiBundle\Domain\ValueObject\AgencyId;

/**
 * Main entry point for REST requests
 */
class RestController extends FOSRestController
{
    /**
     * Main page of API redirects to human representation of entry point
     *
     * @Rest\Get("/")
     * @Rest\View()
     */
    public function indexAction()
    {
        $document = $this->get('bpi.presentation.document');

        // Node resource
        $node = $document->createRootEntity('resource', 'node');
        $hypermedia = $document->createHypermediaSection();
        $node->setHypermedia($hypermedia);
        $hypermedia->addQuery(
            $document->createQuery(
                'item',
                $this->get('router')->generate('node_resource', array(), true),
                array('id'),
                'Find a node by ID'
            )
        );

        $hypermedia->addLink(
            $document->createLink(
                'canonical',
                $this->get('router')->generate('node_resource', array(), true),
                'Node resource'
            )
        );

        $hypermedia->addLink(
            $document->createLink(
                'collection',
                $this->get('router')->generate('list', array(), true),
                'Node collection'
            )
        );

        $hypermedia->addTemplate(
            $template = $document->createTemplate(
                'push',
                $this->get('router')->generate('node_resource', array(), true),
                'Template for pushing node content'
            )
        );

        $hypermedia->addQuery(
            $document->createQuery(
                'statistics',
                $this->get('router')->generate('statistics', array(), true),
                array('dateFrom', 'dateTo'),
                'Statistics for date range'
            )
        );

        $hypermedia->addQuery(
            $document->createQuery(
                'statisticsExtended',
                $this->get('router')->generate('statistics_extended', array(), true),
                array('from', 'to', 'amount', 'action', 'aggregateField', 'contentOwnerAgency'),
                'Statistics '
            )
        );

        $hypermedia->addQuery(
            $document->createQuery(
                'syndicated',
                $this->get('router')->generate('node_syndicated', array(), true),
                array('id'),
                'Notify service about node syndication'
            )
        );

        $hypermedia->addQuery(
            $document->createQuery(
                'delete',
                $this->get('router')->generate('node_delete', array(), true),
                array('id'),
                'Mark node as deleted'
            )
        );

        $template->createField('title');
        $template->createField('body');
        $template->createField('teaser');
        $template->createField('type');
        $template->createField('creation');
        $template->createField('category');
        $template->createField('audience');
        $template->createField('editable');
        $template->createField('authorship');
        $template->createField('agency_id');
        $template->createField('local_id');
        $template->createField('firstname');
        $template->createField('lastname');
        $template->createField('images');
        $template->createField('related_materials');
        $template->createField('url');
        $template->createField('data');

        // Profile resource
        $profile = $document->createRootEntity('resource', 'profile');
        $profile_hypermedia = $document->createHypermediaSection();
        $profile->setHypermedia($profile_hypermedia);
        $profile_hypermedia->addLink(
            $document->createLink(
                'dictionary',
                $this->get('router')->generate('profile_dictionary', array(), true),
                'Profile items dictionary'
            )
        );

        return $document;
    }

    /**
     * Default node listing
     *
     * @Rest\Get("/node/collection")
     * @Rest\View(template="BpiApiBundle:Rest:testinterface.html.twig", statusCode="200")
     */
    public function listAction()
    {
        $facetRepository = $this->getRepository('BpiApiBundle:Entity\Facet');
        $node_query = new \Bpi\ApiBundle\Domain\Entity\NodeQuery();
        $node_query->amount(20);
        if (false !== ($amount = $this->getRequest()->query->get('amount', false))) {
            $node_query->amount($amount);
        }

        if (false !== ($offset = $this->getRequest()->query->get('offset', false))) {
            $node_query->offset($offset);
        }

        if (false !== ($search = $this->getRequest()->query->get('search', false))) {
            $node_query->search($search);
        }

        $filters = array();
        $logicalOperator = '';
        if (false !== ($filter = $this->getRequest()->query->get('filter', false))) {
            foreach ($filter as $field => $value) {
                if ($field == 'category' && !empty($value)) {
                    foreach ($value as $val) {
                        $category = $this
                            ->getRepository('BpiApiBundle:Entity\Category')
                            ->findOneBy(array('category' => $val));
                        if (empty($category)) {
                            continue;
                        }
                        $filters['category'][] = $category;
                    }
                }
                if ($field == 'audience' && !empty($value)) {
                    foreach ($value as $val) {
                        $audience = $this
                            ->getRepository('BpiApiBundle:Entity\Audience')
                            ->findOneBy(array('audience' => $val));
                        if (empty($audience)) {
                            continue;
                        }
                        $filters['audience'][] = $audience;
                    }
                }
                if ($field == 'agency_id' && !empty($value)) {
                    foreach ($value as $val) {
                        if (empty($val)) {
                            continue;
                        }
                        $filters['agency_id'][] = $val;
                    }
                }
                if ($field == 'author' && !empty($value)) {
                    foreach ($value as $val) {
                        if (empty($val)) {
                            continue;
                        }
                        $filters['author'][] = $val;
                    }
                }
            }
            if (isset($filter['agencyInternal'])) {
                $filters['agency_internal'][] = $filter['agencyInternal'];
            }
            if (isset($filter['logicalOperator']) && !empty($filter['logicalOperator'])) {
                $logicalOperator = $filter['logicalOperator'];
            }
        }
        $availableFacets = $facetRepository->getFacetsByRequest($filters, $logicalOperator);
        $node_query->filter($availableFacets->nodeIds);

        if (false !== ($sort = $this->getRequest()->query->get('sort', false))) {
            foreach ($sort as $field => $order) {
                $node_query->sort($field, $order);
            }
        } else {
            $node_query->sort('pushed', 'desc');
        }

        $node_collection = $this->getRepository('BpiApiBundle:Aggregate\Node')->findByNodesQuery($node_query);
        $agency_id = new AgencyId($this->getUser()->getAgencyId()->id());
        foreach ($node_collection as $node) {
            $node->defineAgencyContext($agency_id);
        }

        $document = $this->get("bpi.presentation.transformer")->transformMany($node_collection);
        $router = $this->get('router');
        $document->walkEntities(
            function ($e) use ($document, $router) {
                $hypermedia = $document->createHypermediaSection();
                $e->setHypermedia($hypermedia);
                $hypermedia->addLink(
                    $document->createLink(
                        'self',
                        $router->generate('node', array('id' => $e->property('id')->getValue()), true)
                    )
                );
                $hypermedia->addLink($document->createLink('collection', $router->generate('list', array(), true)));
            }
        );

        // Collection description
        $collection = $document->createEntity('collection');
        $collection->addProperty(
            $document->createProperty(
                'total',
                'integer',
                $node_query->total
            )
        );
        $document->prependEntity($collection);
        $hypermedia = $document->createHypermediaSection();
        $collection->setHypermedia($hypermedia);
        $hypermedia->addLink($document->createLink('canonical', $router->generate('list', array(), true)));
        $hypermedia->addLink(
            $document->createLink('self', $router->generate('list', $this->getRequest()->query->all(), true))
        );
        $hypermedia->addQuery(
            $document->createQuery(
                'refinement',
                $this->get('router')->generate('list', array(), true),
                array(
                    'amount',
                    'offset',
                    'search',
                    $document->createQueryParameter('filter')->setMultiple(),
                    $document->createQueryParameter('sort')->setMultiple(),
                ),
                'List refinements'
            )
        );

        // Prepare facets for xml.
        foreach ($availableFacets->facets as $facetName => $facet) {
            $facetsXml = $document->createEntity('facet', $facetName);
            $result = array();
            foreach ($facet as $key => $term) {
                if ($facetName == 'agency_id') {
                    $result[] = $document->createProperty(
                        $key,
                        'string',
                        $term['count'],
                        $term['agencyName']
                    );
                } else {
                    $result[] = $document->createProperty(
                        $key,
                        'string',
                        $term
                    );
                }
            }

            $facetsXml->addProperties($result);
            $document->prependEntity($facetsXml);
        }


        return $document;
    }

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
     * Shows statictic by AgencyId
     *  - Number of pushed nodes.
     *  - Number of syncidated nodes.
     *
     * @Rest\Get("/statistics")
     * @Rest\View(template="BpiApiBundle:Rest:statistics.html.twig", statusCode="200")
     */
    public function statisticsAction()
    {
        /* @var $request \Symfony\Component\HttpFoundation\Request */
        $request = $this->getRequest();
        $agencyId = $this->getUser()->getAgencyId()->id();

        // @todo Add input validation
        $dateFrom = new \DateTime($request->get('dateFrom'));
        $dateTo = (new \DateTime($request->get('dateTo')))->modify('+23 hours 59 minutes');

        $repo = $this->getRepository('BpiApiBundle:Entity\History');
        $stats = $repo->getStatisticsByDateRangeForAgency($dateFrom, $dateTo, [$agencyId]);

        $document = $this->get("bpi.presentation.transformer")->transform($stats);

        return $document;
    }

    /**
     * Display available options
     *
     * @Rest\Options("/node/collection")
     */
    public function nodeListOptionsAction()
    {
        $options = array(
            'GET' => array(
                'action' => 'List of nodes',
            ),
            'OPTIONS' => array('action' => 'List available options'),
        );
        $headers = array('Allow' => implode(', ', array_keys($options)));

        return $this->handleView($this->view($options, 200, $headers));
    }

    /**
     * List available media type entities
     *
     * @category test interface
     * @Rest\Get("/shema/entity/list")
     * @Rest\View
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
     * @Rest\View
     */
    public function schemaEntityAction($name)
    {
        $loader = new \Bpi\ApiBundle\Tests\DoctrineFixtures\LoadNodes();
        $transformer = $this->get("bpi.presentation.transformer");
        switch ($name) {
            case 'node':
                return $transformer->transform($loader->createAlphaNode());
                break;
            case 'resource':
                return $transformer->transform($loader->createAlphaResource());
                break;
            case 'profile':
                return $transformer->transform($loader->createAlphaProfile());
                break;
            case 'agency':
                return $transformer->transform($loader->createAlphaAgency());
                break;
            case 'nodes_query':
                $doc = $this->get('bpi.presentation.document');
                $doc->appendEntity($entity = $doc->createEntity('nodes_query'));
                $entity->addProperty($doc->createProperty('amount', 'number', 10));
                $entity->addProperty($doc->createProperty('offset', 'number', 0));
                $entity->addProperty($doc->createProperty('filter[resource.title]', 'string', ''));
                $entity->addProperty($doc->createProperty('sort[ctime]', 'string', 'desc'));
                $entity->addProperty(
                    $doc->createProperty('reduce', 'string', 'initial', 'Reduce revisions to initial or latest')
                );

                return $doc;
            default:
                throw new HttpException(404, 'Requested entity does not exists');
        }
    }

    /**
     * Fetches extended statistics.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @Rest\Get("/statisticsExtended")
     * @Rest\View(statusCode="200")
     *
     * @return \Bpi\RestMediaTypeBundle\XmlResponse
     *   Response object.
     */
    public function statisticsExtendedAction(Request $request) {
        $toIsValid = strtotime($request->get('to'));
        $dateTo = new \DateTime($toIsValid ? $request->get('to') : date(DATE_ISO8601));

        $fromIsValid = strtotime($request->get('from'));
        $dateFrom = new \DateTime($fromIsValid ? $request->get('from') : date(DATE_ISO8601));

        $numEntries = $request->get('amount', 10);
        $action = $request->get('action', 'syndicate');
        $aggregate = $request->get('aggregateField', 'agency');
        $aggregate = in_array($aggregate, ['agency', 'node']) ? $aggregate : 'agency';
        $ownerAgency = $request->get('contentOwnerAgency', '');

        /** @var \Bpi\ApiBundle\Domain\Repository\HistoryRepository $repository */
        $repository = $this->getRepository('BpiApiBundle:Entity\History');
        /** @var \Bpi\ApiBundle\Transform\Presentation $transform */
        $transform = $this->get('bpi.presentation.transformer');

        $statExtended = $repository->getActivity(
            $dateFrom,
            $dateTo,
            $action,
            $aggregate,
            !empty($ownerAgency) ? explode(',', $ownerAgency) : [],
            $numEntries
        );

        return $transform->transform($statExtended);
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

        if (!$_node) {
            throw $this->createNotFoundException();
        }
        $_node->defineAgencyContext(new AgencyId($this->getUser()->getAgencyId()->id()));
        $document = $this->get("bpi.presentation.transformer")->transform($_node);

        $hypermedia = $document->createHypermediaSection();
        $node = $document->currentEntity();
        $node->setHypermedia($hypermedia);

        $hypermedia->addLink(
            $document->createLink(
                'self',
                $this->generateUrl('node', array('id' => $node->property('id')->getValue()), true)
            )
        );
        $hypermedia->addLink($document->createLink('collection', $this->generateUrl('list', array(), true)));

        return $document;
    }

    /**
     * Display available options
     * 1. HTTP verbs
     * 2. Expected media type entities in input/output
     *
     * @Rest\Options("/node/item/{id}")
     */
    public function nodeItemOptionsAction($id)
    {
        $options = array(
            'GET' => array(
                'action' => 'Node item',
                'output' => array(
                    'entities' => array(
                        'node'
                    )
                )
            ),
            'POST' => array(
                'action' => 'Post node revision',
                'input' => array(
                    'entities' => array(
                        'agency',
                        'resource',
                        'profile',
                    )
                ),
                'output' => array(
                    'entities' => array(
                        'node'
                    )
                )
            ),
            'OPTIONS' => array('action' => 'List available options'),
        );
        $headers = array('Allow' => implode(', ', array_keys($options)));

        return $this->handleView($this->view($options, 200, $headers));
    }

    /**
     * Push new content
     *
     * @Rest\Post("/node")
     * @Rest\View(template="BpiApiBundle:Rest:testinterface.html.twig", statusCode="201")
     */
    public function postNodeAction()
    {
        $request = $this->getRequest();
        $service = $this->get('domain.push_service');
        $assets = array();

        $facetRepository = $this->getRepository('BpiApiBundle:Entity\Facet');

        /** check request body size, must be smaller than 10MB **/
        if (strlen($request->getContent()) > 10485760) {
            return $this->createErrorView('Request entity too large', 413);
        }

        // request validation
        $violations = $this->isValidForPushNode($request->request->all());
        if (count($violations)) {
            return $this->createErrorView((string) $violations, 422);
        }

        $author = new \Bpi\ApiBundle\Domain\Entity\Author(
            new \Bpi\ApiBundle\Domain\ValueObject\AgencyId($request->get('agency_id')),
            $request->get('local_author_id'),
            $request->get('firstname'),
            $request->get('lastname')
        );

        $filesystem = $service->getFilesystem();

        $resource = new \Bpi\ApiBundle\Domain\Factory\ResourceBuilder($filesystem, $this->get('router'));
        $resource
          ->title($request->get('title'))
          ->body($request->get('body'))
          ->teaser($request->get('teaser'))
          ->url($request->get('url'))
          ->data($request->get('data'))
          ->ctime(\DateTime::createFromFormat(\DateTime::W3C, $request->get('creation')));

        // Related materials
        foreach ($request->get('related_materials', array()) as $material) {
            $resource->addMaterial($material);
        }

        // Download files and add them to resource
        $images = $request->get('assets', array());
        foreach ($images as $image) {
            $imagePath = $image['path'];
            $filename = md5($image['name'] . time());
            $file = $filesystem->createFile($filename);
            // @todo Download files in a proper way.
            $file->setContent(file_get_contents($imagePath));
            $assets[] = array(
                'external' => $imagePath,
                'name' => $filename,
                'title' => !empty($image['title']) ? $image['title'] : null,
                'alt' => !empty($image['alt']) ? $image['alt'] : null,
                'extension' => !empty($image['extension']) ? $image['extension'] : null,
                'type' => !empty($image['type']) ? $image['type'] : null,
                'width' => !empty($image['width']) ? $image['width'] : null,
                'height' => !empty($image['height']) ? $image['height'] : null,
            );
        }
        $resource->addAssets($assets);

        $profile = new \Bpi\ApiBundle\Domain\Entity\Profile();

        $params = new \Bpi\ApiBundle\Domain\Aggregate\Params();
        $params->add(
            new \Bpi\ApiBundle\Domain\ValueObject\Param\Authorship(
                $request->get('authorship')
            )
        );
        $params->add(
            new \Bpi\ApiBundle\Domain\ValueObject\Param\Editable(
                $request->get('editable')
            )
        );

        try {
            // Check for BPI ID
            if ($id = $request->get('bpi_id', false)) {
                if (!$this->getRepository('BpiApiBundle:Aggregate\Node')->find($id)) {
                    return $this->createErrorView(sprintf('Such BPI ID [%s] not found', $id), 422);
                }

                $node = $this->get('domain.push_service')
                  ->pushRevision(new NodeId($id), $author, $resource, $params);

                $facetRepository->prepareFacet($node);

                return $this->get("bpi.presentation.transformer")->transform($node);
            }
            $node = $this->get('domain.push_service')
              ->push($author, $resource, $request->get('category'), $request->get('audience'), $profile, $params);

            $facets = $facetRepository->prepareFacet($node);

            return $this->get("bpi.presentation.transformer")->transform($node);
        } catch (\LogicException $e) {
            return $this->createErrorView($e->getMessage(), 422);
        }
    }

    /**
     *
     * @param string $contents
     * @param int $code
     * @return View
     */
    protected function createErrorView($contents, $code)
    {
        // @todo standart error format
        return $this->view($contents, $code);
    }

    /**
     * Create form to make validation
     *
     * @param array $data
     * @return \Symfony\Component\Validator\ConstraintViolationList
     */
    protected function isValidForPushNode(array $data)
    {
        // @todo move somewhere all this validation stuff
        $node = new Constraints\Collection(array(
            'allowExtraFields' => true,
            'fields' => array(
                // Author
                'agency_id' => array(
                    new Constraints\NotBlank()
                ),
                'local_id' => array(
                    new Constraints\NotBlank()
                ),
                'firstname' => array(
                    new Constraints\Length(array('min' => 2, 'max' => 100))
                ),
                'lastname' => array(
                    new Constraints\Length(array('min' => 2, 'max' => 100))
                ),
                // Resource
                'title' => array(
                    new Constraints\Length(array('min' => 2, 'max' => 500))
                ),
                'body' => array(
                    new Constraints\Length(array('min' => 2))
                ),
                'teaser' => array(
                    new Constraints\Length(array('min' => 2, 'max' => 5000))
                ),
                'creation' => array(
                    //@todo validate against DateTime::W3C format
                    new Constraints\NotBlank()
                ),
                'type' => array(
                    new Constraints\NotBlank()
                ),
                // profile; tags, yearwheel - compulsory
                'category' => array(
                    new Constraints\Length(array('min' => 2, 'max' => 100))
                ),
                'audience' => array(
                    new Constraints\Length(array('min' => 2, 'max' => 100))
                ),
                // params
                /* @todo
                'editable' => array(
                 * new Constraints\Type(array('type' => 'boolean'))
                 * ),
                 * 'authorship' => array(
                 * new Constraints\Type(array('type' => 'boolean'))
                 * ),
                 */
            )
        ));

        $validator = $this->container->get('validator');

        return $validator->validateValue($data, $node);
    }

    /**
     * Asset options
     *
     * @Rest\Options("/node/{node_id}/asset")
     * @Rest\View
     */
    public function nodeAssetOptionsAction($node_id)
    {
        $options = array(
            'PUT' => array(
                'action' => 'Add asset to specific node',
                'input' => array(
                    'entities' => array(
                        'binary file',
                    )
                ),
            ),
            'OPTIONS' => array('action' => 'List available options'),
        );
        $headers = array('Allow' => implode(', ', array_keys($options)));

        return $this->handleView($this->view($options, 200, $headers));
    }

    /**
     * Node options
     *
     * @Rest\Options("/node")
     * @Rest\View(statusCode="200")
     */
    public function nodeOptionsAction()
    {
        $options = array(
            'POST' => array(
                'action' => 'Push new node',
                'template' => array(),
            ),
            'OPTIONS' => array('action' => 'List available options'),
        );
        $headers = array('Allow' => implode(', ', array_keys($options)));

        return $this->handleView($this->view($options, 200, $headers));
    }

    /**
     * Node resource
     *
     * @Rest\Get("/node")
     * @Rest\View(template="BpiApiBundle:Rest:testinterface2.html.twig")
     */
    public function nodeResourceAction()
    {
        // Handle query by node id
        if ($id = $this->getRequest()->get('id')) {
            // SDK can not handle properly redirects, so query string is used
            // @see https://github.com/symfony/symfony/issues/7929
            $params = array(
                'id' => $id,
                '_authorization' => array(
                    'agency' => $this->getUser()->getAgencyId()->id(),
                    'token' => $this->container->get('security.context')->getToken()->token
                )
            );
            return $this->redirect($this->generateUrl('node', $params));
        }

        $document = $this->get('bpi.presentation.document');
        $entity = $document->createRootEntity('node');
        $controls = $document->createHypermediaSection();
        $entity->setHypermedia($controls);
        $controls->addQuery($document->createQuery('search', 'abc', array('id'), 'Find a node by ID'));
        $controls->addQuery($document->createQuery('filter', 'xyz', array('name', 'title'), 'Filtration'));
        $controls->addLink($document->createLink('self', 'Self'));
        $controls->addLink($document->createLink('collection', 'Collection'));

        return $document;
    }

    /**
     * Output media asset
     *
     * @Rest\Get("/asset/{filename}.{extension}")
     */
    public function getAssetAction($filename, $extension)
    {
        $extension = strtolower($extension);

        $mime = 'application/octet-stream';

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $mime = 'image/jpeg';
                break;
            case 'gif':
                $mime = 'image/gif';
                break;
            case 'png':
                $mime = 'image/png';
        }
        $headers = array(
            'Content-Type' => $mime
        );

        try {
            $fs = $this->get('domain.push_service')->getFilesystem();
            $file = $fs->get($filename);
            return new Response($file->getContent(), 200, $headers);
        } catch (\Gaufrette\Exception\FileNotFound $e) {
            throw $this->createNotFoundException();
        } catch (\Exception $e) {
            return new Response('Bad file', 410);
        }
    }

    /**
     * Get profile dictionary
     *
     * @Rest\Get("/profile/dictionary", name="profile_dictionary")
     * @Rest\View(template="BpiApiBundle:Rest:testinterface.html.twig")
     */
    public function profileDictionaryAction()
    {
        $document = $this->get('bpi.presentation.document');

        $audiences = $this->getRepository('BpiApiBundle:Entity\Audience')->findAll();
        $categories = $this->getRepository('BpiApiBundle:Entity\Category')->findAll();

        foreach ($audiences as $audience) {
            $audience->transform($document);
        }

        foreach ($categories as $category) {
            $category->transform($document);
        }

        return $document;
    }

    /**
     * Get profile dictionary options
     *
     * @Rest\Options("/profile_dictionary")
     * @Rest\View(statusCode="200")
     */
    public function profileDictionaryOptionsAction()
    {
        $options = array(
            'GET' => array(
                'action' => 'Get profile dictionary',
                'output' => array(
                    'entities' => array(
                        'profile_dictionary',
                    )
                ),
            ),
            'OPTIONS' => array('action' => 'List available options'),
        );
        $headers = array('Allow' => implode(', ', array_keys($options)));

        return $this->view($options, 200, $headers);
    }

    /**
     * For testing purposes. Echoes back sent request
     *
     * @Rest\Get("/tools/echo")
     * @Rest\View(statusCode="200")
     */
    public function echoAction()
    {
        return $this->view($this->get('request')->getContent(), 200);
    }

    /**
     * Static documentation for the service
     *
     * @Rest\Get("/doc/{page}")
     * @Rest\View(template="BpiApiBundle:Rest:static_doc.html.twig")
     * @param string $page
     */
    public function docAction($page)
    {
        try {
            $file = $this->get('kernel')->locateResource('@BpiApiBundle/Resources/doc/' . $page . '.md');

            return $this->view(file_get_contents($file));
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException();
        }
    }

    /**
     * Mark node as syndicated
     *
     * @Rest\Get("/node/syndicated")
     * @Rest\View(statusCode="200")
     */
    public function nodeSyndicatedAction()
    {
        $id = $this->getRequest()->get('id');
        $agency = $this->getUser();

        $nodeRepository = $this->getRepository('BpiApiBundle:Aggregate\Node');
        $node = $nodeRepository->find($id);
        if (!$node) {
            throw $this->createNotFoundException();
        }

        if ($node->isOwner($agency)) {
            return $this->createErrorView(
                'Not Acceptable: Trying to syndicate content by owner who already did that',
                406
            );
        }

        $log = new History($node, $agency->getAgencyId()->id(), new \DateTime(), 'syndicate');

        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $dm->persist($log);
        $dm->flush($log);

        $nodeSyndications = $node->getSyndications();
        if (null === $nodeSyndications) {
            $node->setSyndications(1);
        } else {
            $node->setSyndications(++$nodeSyndications);
        }

        $dm->persist($node);
        $dm->flush($node);

        return new Response('', 200);
    }

    /**
     * Mark node as deleted
     *
     * @Rest\Get("/node/delete")
     * @Rest\View(statusCode="200")
     */
    public function nodeDeleteAction()
    {
        // @todo Add check if node exists

        $id = $this->getRequest()->get('id');

        $agencyId = $this->getUser()->getAgencyId()->id();

        $node = $this->getRepository('BpiApiBundle:Aggregate\Node')->delete($id, $agencyId);

        if ($node == null) {
            return new Response('This node does not belong to you', 403);
        }

        return new Response('', 200);
    }

    /**
     * Get static images
     *
     * @Rest\Get("/images/{file}.{ext}")
     * @Rest\View(statusCode="200")
     */
    public function staticImagesAction($file, $ext)
    {
        $file = __DIR__ . '/../Resources/public/images/' . $file . '.' . $ext;
        $mime = mime_content_type($file);
        $headers = array(
            'Content-Type' => $mime
        );

        return new Response(file_get_contents($file), 200, $headers);
    }

    /**
     * Get unserialized request body
     *
     * @return \Bpi\RestMediaTypeBundle\Document
     */
    protected function getDocument()
    {
        $request_body = $this->getRequest()->getContent();

        /**
         * @todo validate against schema (logical check)
         */
        if (empty($request_body) || false === simplexml_load_string($request_body)) {
            throw new HttpException(400, 'Bad Request'); // syntax check fail
        }

        $document = $this->get("serializer")->deserialize(
            $request_body,
            'Bpi\RestMediaTypeBundle\Document',
            'xml'
        );
        $document->setRouter($this->get('router'));

        return $document;
    }
}
