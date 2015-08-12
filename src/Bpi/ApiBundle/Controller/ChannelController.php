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
        $agencyRepository = $this->getRepository('BpiApiBundle:Aggregate\Agency');
        $channelData = $this->getAllRequestParameters();
        $channelName = $channelData['name'];

        $requiredChannelData = array(
            'name',
            'agencyPublicId',
            'userExternalId'
        );

        foreach ($requiredChannelData as $dataName) {
            if (!isset($channelData[$dataName]) || empty($channelData[$dataName])) {
                $errorMessage = sprintf('%s required for channel creation.', filter_var($dataName, FILTER_SANITIZE_STRING));
                $statusCode = 400;
                throw new HttpException($statusCode, $errorMessage);
            }
        }

        $similarTitle = $channelRepository->findSimilarByName($channelName);
        if ($similarTitle) {
            $errorMessage = 'Found channel with similar name.';
            $statusCode  = 409;
            throw new HttpException($statusCode, $errorMessage);
        }

        $agency = $agencyRepository->loadUserByUsername($channelData['agencyPublicId']);
        if (null === $agency) {
            $errorMessage = 'Agency with provided public id not found.';
            $statusCode = 404;
            throw new HttpException($statusCode, $errorMessage);
        }

        $user = $userRepository->findBy(array('externalId' => $channelData['userExternalId']));
        if (null === $user) {
            $errorMessage = 'User with provided external id not found.';
            $statusCode = 404;
            throw new HttpException($statusCode, $errorMessage);
        }

        $foundUser = null;
        if (count($user) > 1) {
            foreach ($user as $key => $u) {
                if ($u->getExternalId() === $channelData['userExternalId'] && $u->getUserAgency()->getId() === $channelData['agencyPublicId']) {
                    $foundUser = $u;
                    break;
                }
            }
        } else {
            $foundUser = $user[0];
        }
        if (null === $foundUser) {
            $errorMessage = 'User with provided external id and public agency id not found.';
            $statusCode = 404;
            throw new HttpException($statusCode, $errorMessage);
        }

        $channel = new Channel();
        $channel->setChannelName($channelName);
        $channel->setChannelAdmin($foundUser);

        $dm->persist($channel);
        $dm->flush();

        $chName = filter_var($channel->getChannelName(), FILTER_SANITIZE_STRING);
        $internalUserName = filter_var($channel->getChannelAdmin()->getInternalUserName(), FILTER_SANITIZE_STRING);

        // TODO: Output xml using RestMediaTypeBundle
        $responseContent = sprintf('Channel with name %s and admin user %s created', $chName, $internalUserName);
        return new Response($responseContent, 201);
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
        $incomingData = $this->getRequestParameter('user');
        $channelId = $this->getRequestParameter('channelId');

        $requiredData = array(
            'externalEditorId',
            'agencyPublicId'
        );

        // Check if channel exist
        if (!isset($channelId) || empty($channelId)) {
            $errorMessage = sprintf('%s required to add editor to channel.', filter_var($channelId, FILTER_SANITIZE_STRING));
            $statusCode = 400;
            throw new HttpException($statusCode, $errorMessage);
        }

        // Check required data for each user
        foreach ($incomingData as $key => $data) {
            foreach ($requiredData as $reqData) {
                if (!isset($data[$reqData]) || empty($data[$reqData])) {
                    $errorMessage = sprintf('%s required to add editor to channel.', filter_var($data[$reqData], FILTER_SANITIZE_STRING));
                    $statusCode = 400;
                    throw new HttpException($statusCode, $errorMessage);
                }
            }
        }

        // Check channel exist, load it
        $channel = $channelRepository->find($channelId);
        if (null === $channel) {
            $errorMessage = sprintf('Channel with id %s not found.', filter_var($channelId, FILTER_SANITIZE_STRING));
            $statusCode = 404;
            throw new HttpException($statusCode, $errorMessage);
        }

        // Check if user exist and assign to channel
        foreach ($incomingData as $user) {
            $u = $userRepository->findByExternalIdAgency($user['externalEditorId'], $user['agencyPublicId']);
            if (null === $u) {
                $errorMessage = sprintf(
                    'User with external id %s and agency public id %s not found.',
                    filter_var($user['externalEditorId'], FILTER_SANITIZE_STRING),
                    filter_var($user['agencyPublicId'], FILTER_SANITIZE_STRING)
                );
                $statusCode = 404;
                throw new HttpException($statusCode, $errorMessage);
            }
            $channel->addChannelEditor($u);
        }

        $dm->persist($channel);
        $dm->flush();

        // TODO: Output xml using RestMediaTypeBundle
        return new Response('Editor added to channel', 200);
    }

    /**
     * Remove user from channel
     *
     * @param string $channelId
     *
     * @Rest\Delete("/user/{channelId}")
     * @Rest\View()
     *
     * @return Response
     */
    public function removeEditorFromChannelAction($channelId)
    {
        $dm = $this->getDoctrineManager();
        $channelRepository = $this->getRepository('BpiApiBundle:Entity\Channel');
        $incomingData = $this->getAllQueryParameters();
        $requestingUser = $incomingData['requestingUser'];

        $requiredData = array(
            'externalEditorId',
            'agencyPublicId'
        );

        if (!isset($requestingUser) || empty($requestingUser)) {
            $errorMessage = 'External id of user making request is required.';
            $statusCode = 400;
            throw new HttpException($statusCode, $errorMessage);
        }

        $error = $this->checkIncomingData($incomingData['user'], $requiredData, true);
        if ($error) {
            $errorMessage = $error . ' required for remove user from channel.';
            $statusCode = 400;
            throw new HttpException($statusCode, $errorMessage);
        }

        $countUserToDelete = count($incomingData['user']);
        $channel = $channelRepository->find($channelId);
        $channelEditors = $channel->getChannelEditors();
        $countChannelEditors = $channelEditors->count();

        if ($channelEditors->count() === 0) {
            throw new HttpException(404, 'No editors in this channels.');
        }

        $checksAmount = $countUserToDelete * $countChannelEditors;
        $isAdmin = $channel->getChannelAdmin()->getExternalId() == $requestingUser;
        foreach ($incomingData['user'] as $user) {
            foreach ($channelEditors as $channelEditor) {
                $checkUser = ($user['externalEditorId'] == $channelEditor->getExternalId()) &&
                    ($user['agencyPublicId'] == $channelEditor->getUserAgency());
                $checkUserLeave = $channelEditor->getExternalId() == $requestingUser;
                if (($checkUser && $checkUserLeave) || ($checkUser && $isAdmin)) {
                    $channelEditors->removeElement($channelEditor);
                } else {
                    $checksAmount--;
                }
            }
        }

        if ($checksAmount === 0) {
            throw new HttpException(403, 'Only channel administrator can remove other users.');
        }

        $dm->persist($channel);
        $dm->flush();

        return new Response('Users was removed from channel.', 200);
    }
}