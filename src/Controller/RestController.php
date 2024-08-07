<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2017 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\CreateBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Cmf\Bundle\CoreBundle\Translatable\TranslatableInterface;
use Symfony\Cmf\Bundle\CreateBundle\Security\AccessCheckerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;
use Midgard\CreatePHP\Metadata\RdfTypeFactory;
use Midgard\CreatePHP\RestService;
use Midgard\CreatePHP\RdfMapperInterface;

/**
 * Controller to handle content update callbacks.
 */
class RestController extends AbstractController
{
    /**
     * @var RdfMapperInterface
     */
    protected $rdfMapper;

    /**
     * @var RdfTypeFactory
     */
    protected $typeFactory;

    /**
     * @var RestService
     */
    protected $restHandler;

    /**
     * @var AccessCheckerInterface
     */
    protected $accessChecker;

    /**
     * @var bool
     */
    protected $forceRequestLocale;

    /**
     * @param RdfMapperInterface     $rdfMapper
     * @param RdfTypeFactory         $typeFactory
     * @param RestService            $restHandler
     * @param AccessCheckerInterface $accessChecker
     * @param bool                   $forceRequestLocale
     */
    public function __construct(
        RdfMapperInterface $rdfMapper,
        RdfTypeFactory $typeFactory,
        RestService $restHandler,
        AccessCheckerInterface $accessChecker,
        $forceRequestLocale
    ) {
        $this->rdfMapper = $rdfMapper;
        $this->typeFactory = $typeFactory;
        $this->restHandler = $restHandler;
        $this->accessChecker = $accessChecker;
        $this->forceRequestLocale = $forceRequestLocale;
    }

    protected function getModelBySubject(Request $request, $subject)
    {
        $model = $this->rdfMapper->getBySubject($subject);
        if (empty($model)) {
            throw new NotFoundHttpException($subject.' not found');
        }

        if ($this->forceRequestLocale && $model instanceof TranslatableInterface) {
            $model->setLocale($request->getLocale());
        }

        return $model;
    }

    /**
     * Handle arbitrary methods with the RestHandler.
     *
     * Except for the PUT operation to update a document, operations are
     * registered as workflows.
     *
     * @param Request $request
     * @param string  $subject URL of the subject, ie: /cms/simple/news/news-name
     *
     * @return Response
     *
     * @throws AccessDeniedException if the action is not allowed by the access checker
     *
     * @see RestService::run
     * @since 1.2
     */
    public function updateDocumentAction(Request $request, $subject)
    {
        if (!$this->accessChecker->check($request)) {
            throw new AccessDeniedException();
        }

        $model = $this->getModelBySubject($request, $subject);
        $type = $this->typeFactory->getTypeByObject($model);

        $data = \json_decode($request->getContent(), true);
        $result = $this->restHandler->run($data, $type, $subject, strtolower($request->getMethod()));

        return $this->json($result);
    }

    /**
     * Handle document POST (creation).
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws AccessDeniedException if the action is not allowed by the access checker
     */
    public function postDocumentAction(Request $request)
    {
        if (!$this->accessChecker->check($request)) {
            throw new AccessDeniedException();
        }

        $data = \json_decode($request->getContent(), true);
        $rdfType = trim($data['@type'], '<>');
        $type = $this->typeFactory->getTypeByRdf($rdfType);

        $result = $this->restHandler->run($data, $type, null, RestService::HTTP_POST);

        if (!is_null($result)) {
            return $this->json($result);
        }

        return Response::create('The document was not created', 500);
    }

    /**
     * Get available Workflows for a document.
     *
     * @param Request $request
     * @param string  $subject
     *
     * @return Response
     *
     * @throws AccessDeniedException if getting workflows for this document is
     *                               not allowed by the access checker
     */
    public function workflowsAction(Request $request, $subject)
    {
        if (!$this->accessChecker->check($request)) {
            throw new AccessDeniedException();
        }

        $result = $this->restHandler->getWorkflows($subject);

        return $this->json($result);
    }
}
