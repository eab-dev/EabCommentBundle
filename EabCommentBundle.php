<?php

namespace Eab\CommentBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class EabCommentBundle extends Bundle
{
    // Extend the templates and controllers of ezdemo
    public function getParent()
    {
        return 'FOSCommentBundle';
    }
}
