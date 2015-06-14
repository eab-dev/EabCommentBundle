#Timeago

This bundle uses the [Timeago](http://timeago.yarp.com) jQuery plugin.
It is up to you to include the Javascript plugin. For example,
make sure that `page_head_script.html.twig` includes:

```
{% javascripts
    ...
    '@EabCommentBundle/Resources/public/js/jquery.timeago.js'
%}
    <script type="text/javascript" src="{{ asset_url }}"></script>
{% endjavascripts %}
```

You will get Javascript errors if the plugin isn't included.

If you don't want to use Timeago, copy
`Resources/view/Thread/comments-notimeago-example.html.twig` to
`ezpublish/Resources/EabCommentBundle/view/Thread/comments.html.twig` and
modify it to suit your needs.
