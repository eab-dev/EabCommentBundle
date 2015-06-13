<?php

/**
 * Define a Twig function to get the dimensions of an image.
 * This needs to be provided as a service (ie in services.yml) thus:
 * services:
 *   eab.twig.extension.screenname:
 *       class: Eab\CommentBundle\Twig\Extension\ScreenNameExtension
 *       tags:
 *           - name: twig.extension
 */

namespace Eab\CommentBundle\Twig\Extension;

use eZ\Publish\API\Repository\UserService;

class ScreenNameExtension extends \Twig_Extension
{
    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var array of content fields that make up the user's screenname
     */
    private $userContentFields = array( "first_name", "last_name" );


    /**
     * Constructor
     *
     * @param UserService $userService
     * @param LoggerInterface $logger
     */
    public function __construct( UserService $userService, $userContentFields = array() )
    {
        $this->userService = $userService;
        $this->userContentFields = $userContentFields;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction( 'screenname', array( $this, 'screenName' ) )
        );
    }

    /* Get the screen name of the user with the specified login
     * @param string $login
     * @return string screen name of the user
     */
    public function screenName( $login )
    {
        $user = $this->userService->loadUserByLogin( $login );
        $realName = "";
        foreach( $this->userContentFields as $field ) {
            $realName .= $user->content->getField( $field )->value->text . " ";
        }
        return trim( $realName );
    }

    public function getName()
    {
        return 'screenname';
    }
}
