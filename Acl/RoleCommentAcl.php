<?php

namespace Eab\CommentBundle\Acl;

use FOS\CommentBundle\Acl\RoleCommentAcl as BaseRoleCommentAcl;
use FOS\CommentBundle\Model\CommentInterface;
use FOS\CommentBundle\Model\SignedCommentInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use eZ\Publish\Core\MVC\Symfony\Security\Authorization\Attribute as AuthorizationAttribute;

/* We need to extend the RoleCommentAcl class in order to access the security
 * context. We can then test if it matches the author of a comment and if so
 * allow the current user to edit and delete.
 */

class RoleCommentAcl extends BaseRoleCommentAcl
{
    /**
     * The current Security Context.
     *
     * @var SecurityContextInterface
     */
    private $securityContext;

    /**
     * Constructor.
     *
     * @param SecurityContextInterface $securityContext
     * @param string                   $createRole
     * @param string                   $viewRole
     * @param string                   $editRole
     * @param string                   $deleteRole
     * @param string                   $commentClass
     */
    public function __construct(
                                    SecurityContextInterface $securityContext,
                                    $createRole,
                                    $viewRole,
                                    $editRole,
                                    $deleteRole,
                                    $commentClass
    )
    {
        parent::__construct(
            $securityContext,
            $createRole,
            $viewRole,
            $editRole,
            $deleteRole,
            $commentClass);

        $this->securityContext = $securityContext;
    }

    /**
     * Check if the Security token has an appropriate role to edit the supplied Comment.
     *
     * @param  CommentInterface $comment
     * @return boolean
     */
    public function canEdit(CommentInterface $comment)
    {
        if ($comment instanceof SignedCommentInterface)
        {
            if ( $this->currentUserMatchesAuthor( $comment ) ) {
                return true;
            }
        }
        return parent::canEdit($comment);
    }

    /**
     * Checks if the Security token is allowed to delete a specific Comment.
     *
     * @param  CommentInterface $comment
     * @return boolean
     */
    public function canDelete(CommentInterface $comment)
    {
        if ($comment instanceof SignedCommentInterface)
        {
            if ( $this->currentUserMatchesAuthor( $comment ) ) {
                return true;
            }
        }
        return parent::canDelete($comment);
    }

    /**
     * Test if the Security token matches the author of the comment.
     *
     * @param  CommentInterface $comment
     * @return boolean
     */
    public function currentUserMatchesAuthor(CommentInterface $comment)
    {
        $currentUser = $this->securityContext->getToken()->getUser();
        if ( $currentUser != "anon." ) {

            // We need a policy like 'foscomments/manage'
            $authorizationAttribute = new AuthorizationAttribute( 'websitetoolbar', 'use' );

            $granted = $this->securityContext->isGranted( $authorizationAttribute );
            if ( $granted ) {
                return true; // treat manager like author
            }
            return $comment->getAuthor() == $currentUser->getAPIUser()->login;
        }
        return false;
    }
}
