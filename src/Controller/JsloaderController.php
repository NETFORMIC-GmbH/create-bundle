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
use Symfony\Cmf\Bundle\CreateBundle\Security\AccessCheckerInterface;
use Symfony\Cmf\Bundle\MediaBundle\File\BrowserFileHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * This controller includes the correct twig file to bootstrap the javascript
 * files of create.js and its dependencies if the current user has the rights
 * to use create.js.
 *
 * The security context is optional to not fail with an exception if the
 * controller is loaded in a context without a firewall.
 */
class JsloaderController extends AbstractController
{
    /**
     * @var AccessCheckerInterface
     */
    protected $accessChecker;

    /**
     * @var string
     */
    private $stanbolUrl;

    /**
     * @var bool
     */
    private $fixedToolbar;

    /**
     * @var array
     */
    private $plainTextTypes;

    /**
     * @var string
     */
    private $editorBasePath;

    /**
     * @var bool
     */
    private $imageUploadEnabled;

    /**
     * @var BrowserFileHelper
     */
    private $browserFileHelper;

    /**
     * Create the Controller.
     *
     * @param AccessCheckerInterface   $accessChecker
     * @param string                   $stanbolUrl         the url to use for the semantic
     *                                                     enhancer stanbol
     * @param bool                     $imageUploadEnabled used to determine whether image
     *                                                     upload should be activated
     * @param bool                     $fixedToolbar       whether the toolbar is fixed or
     *                                                     floating (Hallo editor specific)
     * @param array                    $plainTextTypes     the RDFa types to edit in raw text only
     * @param string|bool              $requiredRole       role a user needs to be granted in order
     *                                                     to see the the editor. If set to false,
     *                                                     the editor is always loaded
     * @param SecurityContextInterface $securityContext    the security context to use to check for
     *                                                     the role
     * @param string                   $editorBasePath     Configuration for CKeditor
     * @param BrowserFileHelper        $browserFileHelper  Used to determine image editing for ckeditor
     */
    public function __construct(
        AccessCheckerInterface $accessChecker,
        $stanbolUrl = false,
        $imageUploadEnabled = false,
        $fixedToolbar = true,
        $plainTextTypes = array(),
        $editorBasePath = null,
        BrowserFileHelper $browserFileHelper = null
    ) {
        $this->accessChecker = $accessChecker;
        $this->stanbolUrl = $stanbolUrl;
        $this->imageUploadEnabled = $imageUploadEnabled;
        $this->fixedToolbar = $fixedToolbar;
        $this->plainTextTypes = $plainTextTypes;
        $this->editorBasePath = $editorBasePath;
        $this->browserFileHelper = $browserFileHelper;
    }

    /**
     * Render javascript HTML tags for create.js and dependencies and bootstrap
     * javscript code.
     *
     * This bundle comes with templates for ckeditor, hallo and to develop on
     * the hallo coffeescript files.
     *
     * To use a different editor simply create a template following the naming
     * below:
     *   CmfCreate/includejsfiles-%editor%.html.twig
     * and pass the appropriate editor name.
     *
     * @param Request $request the request object for the AccessChecker
     * @param string  $editor  the name of the editor to load
     */
    public function includeJSFilesAction(Request $request, $editor = 'ckeditor')
    {
        if (!$this->accessChecker->check($request)) {
            return new Response('');
        }

        if ($this->browserFileHelper) {
            $helper = $this->browserFileHelper->getEditorHelper($editor);
            $browseUrl = $helper ? $helper->getUrl() : false;
        } else {
            $browseUrl = false;
        }

        return $this->render(
            \sprintf('@CmfCreate/includejsfiles-%s.html.twig', $editor),
            array(
                'cmfCreateEditor' => $editor,
                'cmfCreateStanbolUrl' => $this->stanbolUrl,
                'cmfCreateImageUploadEnabled' => (bool) $this->imageUploadEnabled,
                'cmfCreateFixedToolbar' => (bool) $this->fixedToolbar,
                'cmfCreatePlainTextTypes' => json_encode($this->plainTextTypes),
                'cmfCreateEditorBasePath' => $this->editorBasePath,
                'cmfCreateBrowseUrl' => $browseUrl,
            )
        );
    }
}
