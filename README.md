EABCommentBundle
================

Yet another comments bundle! This one is an integration of FOSCommentBundle with eZ Publish.

Summary
-------

EABCommentBundle integrates FOSCommentBundle with eZ Publish.
It also provides the following additional features:

* Configured so that only logged in users can create comments

* Ordinary eZ Publish users can edit or delete their own comments but can't edit or delete comments created by others

* Managers (defined as eZ Publish users with the `websitetoolbar/use` policy) can edit or delete anyone's comments

* Log comments

* Sends a notification email when a user makes a comment

* A controller and view template to display the comment count for a content object

* A Twig function allowing you to customize which user content fields are used to display the name of a comment's author

* Integration of [Timeago](http://timeago.yarp.com) jQuery plugin to display dates in fuzzy relative time.
  [More information](Resources/doc/Timeago.md)

Installation
------------

1. If you haven't done so already, add the Doctrine ORM bundle:

   ```
  composer require --update-no-dev --prefer-dist doctrine/orm
   ```

   Edit `ezpublish/config/ezpublish.yml` and add under `doctrine` section:

```
    orm:
        auto_generate_proxy_classes: %kernel.debug%
        auto_mapping: true
```

2. Update the database with the new entities (you should back up the database first to be safe):

```
php ezpublish/console doctrine:schema:update --force
```

3. Follow the [installation instructions](https://github.com/FriendsOfSymfony/FOSCommentBundle/blob/master/Resources/doc/1-setting_up_the_bundle.md)
for FOSCommentBundle except when editing `ezpublish/routing.yml` add:

  ```
  fos_comment_api:
      resource: "../../vendor/friendsofsymfony/comment-bundle/FOS/CommentBundle/Resources/config/routing.yml"
      prefix: /api
      type: rest
      defaults: { _format: html }
  ```

Explanation: because this bundle override FOSCommentBundle we have to define the resource
using its file path instead of `resource: "@FOSCommentBundle/Resources/config/routing.yml"`.

4. Edit `ezpublish/config.yml` and add the bundle to the Assetic configuration:

    ```
    assetic:
        bundles:
            ...
            - FOSCommentBundle
    ```
    Add the following block:

    ```
  # Settings for FOSCommentBundle and EABCommentBundle
  fos_comment:
      db_driver: orm
      class:
          model:
              comment: Eab\CommentBundle\Entity\Comment
              thread: Eab\CommentBundle\Entity\Thread
      acl: true
      service:
          acl:
              thread: fos_comment.acl.thread.roles
              comment: eab.comment.acl.comment.roles
              vote: fos_comment.acl.vote.roles
      acl_roles:
          comment:
              create: IS_AUTHENTICATED_REMEMBERED
              view: IS_AUTHENTICATED_ANONYMOUSLY
              edit: ROLE_ADMIN
              delete: ROLE_ADMIN
          thread:
              create: IS_AUTHENTICATED_REMEMBERED
              view: IS_AUTHENTICATED_ANONYMOUSLY
              edit: ROLE_ADMIN
              delete: ROLE_ADMIN
          vote:
              create: IS_AUTHENTICATED_REMEMBERED
              view: IS_AUTHENTICATED_ANONYMOUSLY
              edit: ROLE_ADMIN
              delete: ROLE_ADMIN
  ```

5. Edit `ezpublish/EzPublishKernel.php` and add the following line above your main bundle:

   ```
   new Eab\CommentBundle\EabCommentBundle(),
   ```

   Explanation: by adding it above your main bundle you can add settings to your
   bundle that will override EABCommentBundle's settings.

6. Include the Timeago jQuery plugin. For example, in `page_head_script.html.twig`:

    ```
    {% javascripts
        ...
        '@EabCommentBundle/Resources/public/js/jquery.timeago.js'
    %}
        <script type="text/javascript" src="{{ asset_url }}"></script>
    {% endjavascripts %}
    ```

   Read [more](Resources/doc/Timeago.md) if you don't want to use Timeago.

7. Decide if you want to send notification emails in real time or spool them.
   If you want to spool them, then edit the `swiftmailer` section of `ezpublish/config.yml` and replace:

   ```
   swiftmailer:
      spool: { type: memory }
   ```

   with:

   ```
   swiftmailer:
     spool:
        type: file
        path: %kernel.root_dir%/mail/queue
   ```

8. Decide if you want to log each comment to a log file. If you do, then edit the `monolog` section of
`ezpublish/config.yml` and add:

  ```
  monolog:
      handlers:
           comments:
               type: stream
               path: ../ezpublish/logs/comments.log
               channels: [comments]
       channels:
               - eab
               - comments
  ```

  Note that this will make all emails spooled, not just those sent by EABCommentBundle.
  Set up a cronjob to send all the spooled emails. For example:

  ```
  # Every minute, spend 10 seconds sending spooled emails
  * * * * * cd /path/to/ezpublish && php ezpublish/console swiftmailer:spool:send --time-limit=10
  ```

Customization
-------------

In your own bundle's `services.yml` you can customize the sender and receiver email addresses used for
notification, and how to construct the comment author's name (only fields of type `ezstring` can be used).
For example:

```
# Settings for comments
    eab.comment.mail.sender: webmaster@mywebsite.com
    eab.comment.mail.receiver: manager@mycompany.com
    eab.screenname.user.fields: [ first_name ]
```
