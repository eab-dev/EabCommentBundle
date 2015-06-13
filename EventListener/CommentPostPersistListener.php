<?php

/*  */

namespace Eab\CommentBundle\EventListener;

use FOS\CommentBundle\Events;
use FOS\CommentBundle\Event\CommentEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;
use eZ\Publish\API\Repository\UserService;

/**
 * Listen for the event 'onCommentPersist'which is fired after a comment has
 * been persisted. Then send an email to an administrator and record it in the
 * log file.
 */
class CommentPostPersistListener implements EventSubscriberInterface
{
    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var Swift_Mailer
     */
    private $mailer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Email address of sender
     */
    private $sender;

    /**
     * @var Email address of receiver
     */
    private $receiver;

    /**
     * Constructor
     *
     * @param UserService $userService
     * @param LoggerInterface $logger
     */
    public function __construct(
                                UserService $userService,
                                \Swift_Mailer $mailer,
                                $sender,
                                $receiver,
                                LoggerInterface $logger = null
                            )
    {
        $this->userService = $userService;
        $this->mailer = $mailer;
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->logger = $logger;
    }

    /**
     * After a comment has been persisted, notify someone
     *
     * @param \FOS\CommentBundle\Event\ThreadEvent $event
     * @return void
     */
    public function onCommentPersist( CommentEvent $event )
    {
        $comment = $event->getComment();
        $thread = $comment->getThread();

        $user = $this->userService->loadUserByLogin( $comment->getAuthor() );

        // Get the email address and real name; later we'll add the alias
        $email = $user->content->getField( 'user_account' )->value->email;
        $realName = $user->content->getField( 'first_name' )->value->text . " "
                    . $user->content->getField( 'last_name' )->value->text;

        $subject = "New comment";

        $body = "The user $realName <$email> added the following comment to " . $thread->getPermalink() . ":\n\n";
        $body .= $comment->getBody();
        $body .= "\n\n";

        // send the comment by email
        $mail = \Swift_Message::newInstance()
                    ->setFrom( $this->sender )
                    ->setTo( $this->receiver )
                    ->setSubject( $subject )
                    ->setBody( $body );
        $this->mailer->send( $mail );

        // log the comment as well
        $this->logger->info( $body );

    }

    public static function getSubscribedEvents()
    {
        return array( Events::COMMENT_POST_PERSIST => 'onCommentPersist' );
    }
}
