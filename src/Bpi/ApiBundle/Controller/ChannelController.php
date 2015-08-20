<?php
/**
 * @file
 *  Channel controller class
 */

namespace Bpi\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints;

use FOS\RestBundle\Controller\Annotations as Rest;

use Bpi\ApiBundle\Domain\Entity\Channel;
use Bpi\RestMediaTypeBundle\Document;
use Bpi\ApiBundle\Domain\Entity\History;

/**
 * Class ChannelController
 * @package Bpi\ApiBundle\Controller
 *
 * Rest controller for Channels
 */
class ChannelController extends BPIController
{
    /**
     * List all channels
     *
     * @Rest\Get("/")
     * @Rest\View()
     * @return \HttpResponse
     */
    public function listChannelsAction()
    {
        $channelRepository = $this->getRepository('BpiApiBundle:Entity\Channel');
        $serializer = $this->getSerialilzer();

        $allChannels = $channelRepository->findAll();

        $serializedData = '';

        // TODO: Output xml using RestMediaTypeBundle
        return new Response($serializedData, 200);
    }

    /**
     * List channels of given user
     *
     * @param $userExternalId
     * @param $userAgencyId
     *
     * @Rest\Get("/user/{userExternalId}/{userAgencyId}")
     * @Rest\View()
     *
     * @return Document $document
     */
    public function listUsersChannelsAction($userExternalId, $userAgencyId)
    {
        if (!isset($userExternalId) || empty($userExternalId)) {
            throw new HttpException(400, 'User external id required for listing channels.');
        }

        if (!isset($userAgencyId) || empty($userAgencyId)) {
            throw new HttpException(400, 'User agency required for listing channels.');
        }

        $dm = $this->getDoctrineManager();
        $userRepository = $this->getRepository('BpiApiBundle:Entity\User');
        $channelRepository = $this->getRepository('BpiApiBundle:Entity\Channel');
        $agencyRepository = $this->getRepository('BpiApiBundle:Aggregate\Agency');

        $userAgency = $agencyRepository->findOneBy(array('public_id' => $userAgencyId));

        if (null === $userAgency) {
            throw new HttpException(404, 'Agency with external id ' . $userAgencyId . ' not found.');
        }

        $user = $userRepository->findOneBy(
            array(
                'externalId' => $userExternalId,
                'userAgency.$id' => new \MongoId($userAgency->getId())
            )
        );

        if (null === $user) {
            $message = 'User with given externalId: ' . $userExternalId . ' and agency public_id: ' . $userAgencyId . ' not found.';
            throw new HttpException(404, $message);
        }

        $channels = $channelRepository->findChannelsByUser($user);

        $document = $this->get('bpi.presentation.transformer')->transformMany($channels);
        $router = $this->get('router');
        $document->walkEntities(
            function($e) use ($document, $router, $userExternalId, $userAgencyId) {
                $hypermedia = $document->createHypermediaSection();
                $e->setHypermedia($hypermedia);
                $hypermedia->addLink(
                    $document->createLink(
                        'self',
                        $router->generate('list_users_channels', array(
                            'userExternalId' => $userExternalId,
                            'userAgencyId' => $userAgencyId
                        ), true)
                    )
                );
                $hypermedia->addLink($document->createLink('channel', $router->generate('list_channels', array(), true)));
            }
        );

        return $document;
    }

    /**
     * Create new channel
     *
     * @Rest\Post("/")
     * @Rest\View(statusCode="201")
     */
    public function createChannelAction()
    {
        $dm = $this->getDoctrineManager();
        $channelRepository = $this->getRepository('BpiApiBundle:Entity\Channel');
        $userRepository = $this->getRepository('BpiApiBundle:Entity\User');
        $params = $this->getAllRequestParameters();
        // Strip all params.
        $this->stripParams($params);

        $requiredParams = array(
            'name' => 0,
            'editorId' => 0,
        );
        $this->checkParams($params, $requiredParams);

        foreach ($requiredParams as $param => $count) {
            if ($count  == 0) {
                throw new HttpException(400, "Param '{$param}' is required.");
            }
        }

        $similarTitle = $channelRepository->findSimilarByName($params['name']);
        if ($similarTitle) {
            throw new HttpException(409, "Channel with name = '{$params['name']}' already exists.");
        }

        $user = $userRepository->findOneById($params['editorId']);
        if ($user === null) {
            throw new HttpException(404, "User with id = '{$params['editorId']}' not found.");
        }

        $channel = new Channel();
        $channel->setChannelName($params['name']);
        $channel->setChannelAdmin($user);

        $dm->persist($channel);
        $dm->flush();

        $document = $this->get('bpi.presentation.transformer')->transform($channel);

        return $document;
    }

    /**
     * Add editors to channels
     *
     * @Rest\Post("/add/editor")
     * @Rest\View()
     */
    public function addEditorToChannelAction()
    {
        $dm = $this->getDoctrineManager();
        $userRepository = $this->getRepository('BpiApiBundle:Entity\User');
        $channelRepository = $this->getRepository('BpiApiBundle:Entity\Channel');
        $params = $this->getAllRequestParameters();
        // Strip all params.
        $this->stripParams($params);

        $requiredParams = array(
            'channelId' => 0,
            'adminId' => 0,
            'editorId' => 0,
        );
        $this->checkParams($params, $requiredParams);

        foreach ($requiredParams as $param => $count) {
            if ($count  == 0) {
                throw new HttpException(400, "Param '{$param}' is required.");
            }
        }

        // Check channel exist, load it.
        $channel = $channelRepository->findOneById($params['channelId']);
        if ($channel === null) {
            throw new HttpException(404, "Channel with id = '{$params['channelId']}' not found.");
        }

        // Check if user have permission to add node to channel.
        $admin = $channel->getChannelAdmin();
        if ($admin->getId() != $params['adminId']) {
            throw new HttpException(404, "User with id  = '{$params['adminId']}' can't add users to channel.");
        }

        $skipped = array();
        $editors = $channel->getChannelEditors();
        $count = 0;
        foreach ($params['users'] as $user) {
            $u = $userRepository->findOneById($user['editorId']);

            if ($u === null || $editors->contains($u) || $admin == $user) {
                $skipped[] = $user['editorId'];
                continue;
            }

            $channel->addChannelEditor($u);
            $count++;
        }

        $dm->persist($channel);
        $dm->flush();

       $message = "{$count} user(s) was successfully added to channel.";
        if (!empty($skipped)) {
            $message = $message . " " . count($skipped). " user(s) was skipped (" . implode(', ', $skipped) . ").";
        }
        return new Response($message, 200);
    }

    /**
     * Remove user from channel
     *
     * @Rest\Post("/remove/editor")
     * @Rest\View()
     *
     * @return Response
     */
    public function removeEditorFromChannelAction($channelId)
    {
        $dm = $this->getDoctrineManager();
        $userRepository = $this->getRepository('BpiApiBundle:Entity\User');
        $channelRepository = $this->getRepository('BpiApiBundle:Entity\Channel');
        $params = $this->getAllRequestParameters();
        // Strip all params.
        $this->stripParams($params);

        $requiredParams = array(
            'channelId' => 0,
            'adminId' => 0,
            'editorId' => 0,
        );
        $this->checkParams($params, $requiredParams);

        foreach ($requiredParams as $param => $count) {
            if ($count  == 0) {
                throw new HttpException(400, "Param '{$param}' is required.");
            }
        }

        // Check channel exist, load it.
        $channel = $channelRepository->findOneById($params['channelId']);
        if ($channel === null) {
            throw new HttpException(404, "Channel with id = '{$params['channelId']}' not found.");
        }

        // Check if user have permission to add node to channel.
        $admin = $channel->getChannelAdmin();
        if ($admin->getId() != $params['adminId']) {
            throw new HttpException(404, "User with id  = '{$params['adminId']}' can't remove users from channel.");
        }

        $skipped = array();
        $editors = $channel->getChannelEditors();
        $count = 0;
        foreach ($params['users'] as $user) {
            $u = $userRepository->findOneById($user['editorId']);

            if ($u === null || !$editors->contains($u) || $admin == $user) {
                $skipped[] = $user['editorId'];
                continue;
            }

            $channel->removeChannelEditor($u);
            $count++;
        }

        $dm->persist($channel);
        $dm->flush();

       $message = "{$count} user(s) was successfully removed from channel.";
        if (!empty($skipped)) {
            $message = $message . " " . count($skipped). " user(s) was skipped (" . implode(', ', $skipped) . ").";
        }
        return new Response($message, 200);
    }

    /**
     * Add node to channel.
     *
     * @Rest\Post("/add/node")
     * @Rest\View()
     *
     * @return Response
     */
    public function addNodeToChannelAction()
    {
        $dm = $this->getDoctrineManager();
        $channelRepository = $this->getRepository('BpiApiBundle:Entity\Channel');
        $nodeRepository = $this->getRepository('BpiApiBundle:Aggregate\Node');
        $userRepository = $this->getRepository('BpiApiBundle:Entity\User');

        $params = $this->getAllRequestParameters();
        // Strip all params.
               $this->stripParams($params);

        $requiredParams = array(
            'nodeId' => 0,
            'editorId' => 0,
            'channelId' => 0,
        );
        $this->checkParams($params, $requiredParams);

        foreach ($requiredParams as $param => $count) {
            if ($count  == 0) {
                throw new HttpException(400, "Param '{$param}' is required.");
            }
        }

        // Try to load channel.
        $channel = $channelRepository->findOneById($params['channelId']);
        if ($channel === null) {
            throw new HttpException(404, "Channel with id  = '{$params['channelId']}' not found.");
        }

        // Check if user have permission to add node to channel.
        $admin = $channel->getChannelAdmin();
        $editors = $channel->getChannelEditors();
        if ($admin->getId() != $params['editorId'] && !$editors->contains($params['editorId'])) {
            throw new HttpException(404, "User with id  = '{$params['editorId']}' can't push to this channel.");
        }

        $count = 0;
        $skipped = array();
        foreach ($params['nodes'] as $data) {
            // Check node exist, load it.
            $node = $nodeRepository->findOneById($data['nodeId']);
            if ($node === null) {
                throw new HttpException(404, "Node with id  = '{$data['nodeId']}' not found.");
            }

            $nodes = $channel->getChannelNodes();
            if ($nodes->contains($node)) {
                $skipped[] = $node->getId();
                continue;
            }

            // Try to add node.
            try {
                $channel->addChannelNode($node);
                $count++;
            } catch (Exception $e) {
                return new Response('Internal error on adding node.', 500);
            }
        }

        $dm->persist($channel);
        $dm->flush();

        $message = "{$count} node(s) was successfully added to channel.";
        if (!empty($skipped)) {
            $message = $message . " " . count($skipped). " node(s)  already added to channel (" . implode(', ', $skipped) . ").";
        }
        return new Response($message, 200);
    }

    /**
     * Remove node to channel.
     *
     * @Rest\Post("/remove/node")
     * @Rest\View()
     *
     * @return Response
     */
    public function removeNodeFromChannelAction()
    {
        $dm = $this->getDoctrineManager();
        $channelRepository = $this->getRepository('BpiApiBundle:Entity\Channel');
        $nodeRepository = $this->getRepository('BpiApiBundle:Aggregate\Node');
        $userRepository = $this->getRepository('BpiApiBundle:Entity\User');

        $params = $this->getAllRequestParameters();
        // Strip all params.
        $this->stripParams($params);

        $requiredParams = array(
            'nodeId' => 0,
            'editorId' => 0,
            'channelId' => 0,
        );
        $this->checkParams($params, $requiredParams);

        foreach ($requiredParams as $param => $count) {
            if ($count  == 0) {
                throw new HttpException(400, "Param '{$param}' is required.");
            }
        }

        // Try to load channel.
        $channel = $channelRepository->findOneById($params['channelId']);
        if ($channel === null) {
            throw new HttpException(404, "Channel with id  = '{$params['channelId']}' not found.");
        }

        // Check if user have permission to add node to channel.
        $admin = $channel->getChannelAdmin();
        $editors = $channel->getChannelEditors();
        if ($admin->getId() != $params['editorId'] && !$editors->contains($params['editorId'])) {
            throw new HttpException(404, "User with id  = '{$params['editorId']}' can't push to this channel.");
        }

        $count = 0;
        $skipped = array();
        foreach ($params['nodes'] as $data) {
            // Check node exist, load it.
            $node = $nodeRepository->findOneById($data['nodeId']);
            if ($node === null) {
                throw new HttpException(404, "Node with id  = '{$data['nodeId']}' not found.");
            }

            $nodes = $channel->getChannelNodes();
            if (!$nodes->contains($node)) {
                $skipped[] = $node->getId();
                continue;
            }

            // Try to add node.
            try {
                $channel->removeChannelNode($node);
                $count++;
            } catch (Exception $e) {
                return new Response('Internal error on removing node.', 500);
            }
        }

        $dm->persist($channel);
        $dm->flush();

        $message = "{$count} node(s) was successfully removed from channel.";
        if (!empty($skipped)) {
            $message = $message . " " . count($skipped). " node(s) not added to channel (" . implode(', ', $skipped) . ").";
        }
        return new Response($message, 200);
    }
}
