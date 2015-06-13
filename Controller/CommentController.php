<?php

namespace Eab\CommentBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use FOS\CommentBundle\Model\ThreadInterface;
use Symfony\Component\HttpFoundation\Response;

class CommentController extends Controller
{
    /**
     * Render the comment count for a given thread.
     *
     * @param string $id
     *
     * @return View
     */
    public function countCommentsAction( $id = null, $location = null )
    {
        if ( null === $id ) {
            $id = $this->encodePathString( $location->pathString );
        }

        $manager = $this->container->get( 'fos_comment.manager.thread' );
        $thread = $manager->findThreadById( $id );

        if ( null === $thread ) {
            $limit = 0;
        } else {
            $limit = $thread->getNumComments();
        }

        return $this->render( 'EabCommentBundle::count.html.twig', array( 'count' => $limit ));
    }

    /**
     * Displays the most commented locations (in a subtree)
     *
     * @param (optional) int $limit Number of locations to display, default is 5
     * @param (optional) string $subtree Limit the locations to within a subtree
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function mostCommentedAction( $limit = 5, $subtree = false )
    {
        $response = new Response();
        $response->setSharedMaxAge( 3600 ); // cache for 1 hour

        $this->get( 'monolog.logger.eab' )->info( "CommentController::mostCommentedAction: started" );

        //$subtree = "/1/2/594/";

        // Query the Doctrine repository to get Threads with the highest number of comments
        $threadQuery = $this->getDoctrine()->getManager()->createQuery(
                    'SELECT T.id, T.numComments'
                    . ' FROM EabCommentBundle:Thread T'
                    . ' WHERE T.numComments > 0'
                    . ( $subtree ? ' AND T.id LIKE :encodedSubtree' : '' )
                    . ' ORDER BY T.numComments DESC' );
        if ( $subtree ) {
            $encodedSubtree = $this->encodePathString( $subtree );
            $threadQuery->setParameter( 'encodedSubtree', $encodedSubtree . '%' );
        }
        $threadQuery->setMaxResults( $limit );
        $threads = $threadQuery->getResult();

        // Get location IDs from Thread IDs
        $locationIds = array();
        $numComments = array();
        foreach ( $threads as $index => $thread ) {
            $locationIds[] = $this->getLocationIdFromThreadId( $thread[ 'id' ] );
            $numComments[ $index ] = $this->getLocationIdFromThreadId( $thread[ 'numComments' ] );
        }

        // Query the eZ Publish repository to get the locations matching the Thread IDs
        $locations = $this->fetchLocationsById( $locationIds );

        return $this->render(
            'EabCommentBundle::most_commented.html.twig',
            array( 'locations' => $locations,
                   'numComments' => $numComments ),
            $response
        );
    }

    private function fetchLocationsById( $locationIds )
    {
        $locations = array();
        $locationService = $this->getRepository()->getLocationService();
        foreach ( $locationIds as $locationId ) {
            $locations[] = $locationService->loadLocation( $locationId );
        }
        return $locations;

        /* If the results should be in order of number of comments we can only fetch them
         * one at a time. eZ Publish has no way of ordering them in a single query because
         * it doesn't know about the comments. Another strategy might be to add
         * a 'Number of Comments' meta field to the article content type, write a pre-persist
         * event listener to update it when a comment is published, and rewrite this
         * controller to get the top articles in a single query. Then it would not be
         * necessary to query the Threads table to get the most commented list.
         * If the order doesn't matter we can use:
         *
         * $query = new LocationQuery();
         * $query->criterion = new Criterion\LocationId( $locationIds );
         * $query->limit = $limit;
         * $query->offset = 0;
         * return $this->getRepository()->getSearchService()->findLocations( $query )->searchHits;
         */
    }

    /**
     * Convert eZ Publish path string into a form that works with the FOSCommentBundle API
     *
     * @param string $subtree Path string from eZ Publish
     * @return string Encoded path string for FOSCommentBundle
     */
    private static function encodePathString( $subtree )
    {
        return implode( '-', explode( '/', $subtree ) );
    }

    /**
     * Convert FOSCommentBundle API encoded path string into eZ Publish form
     *
     * @param string $subtree Path string from FOSCommentBundle
     * @return string Encoded path string for eZ Publish
     */
    private static function decodePathString( $subtree )
    {
        return implode( '/', explode( '-', $subtree ) );
    }

    /**
     * Extract the Location ID from a Thread ID (ie an encoded path string)
     *
     * @param string $threadID Thread ID (encoded path string) from FOSCommentBundle
     * @return int Location ID
     */
    private static function getLocationIdFromThreadId( $threadID )
    {
        return (int) end( explode( '-', trim( $threadID, '-' ) ) );
    }
}
