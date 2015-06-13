<?php

namespace Eab\CommentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\CommentBundle\Entity\Comment as BaseComment;
use FOS\CommentBundle\Model\SignedCommentInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class Comment extends BaseComment implements SignedCommentInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Thread of this comment
     *
     * @var Thread
     * @ORM\ManyToOne(targetEntity="Eab\CommentBundle\Entity\Thread")
     */
    protected $thread;

    /**
     * Author of the comment, NULL means anonymous
     *
     * @var string $author
     * @ORM\Column(type="string", nullable=true)
     */
    protected $author;

    /**
     * Set the author to the current eZ Publish user's login
     * @var UserInterface $author
     */
    public function setAuthor(UserInterface $author)
    {
        /* It would be more efficient to have join to a User entity, store the ID
         * and the user's real name (or alias) in the table. Currently to look up
         * each user's real name (or alias) requires first running a query for
         * each author in getAuthorName() ie 2 queries rather than 1.
         * Also, using the service container from an entity is regarded as
         * bad practice.
         */
        // Something like $author->getAPIUser()->content->fields['first_name'] should work
        // or maybe content->getFieldValue( 'first_name' )
        $login = $author->getAPIUser()->login;
        //$id = $author->getAPIUser()->id;
        //$roles = $author->getRoles();
        $this->author = $login;
    }

    public function getAuthor()
    {
        if ( $this->author == "anonymous" ) {
            return null;
        }
        return $this->author;
    }

    public function getAuthorName()
    {
        $author = $this->getAuthor();
        if ( null === $author or "anonymous" == $author ) {
            return 'Anonymous';
        }
        return $author;
    }
}
