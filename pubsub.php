<?php
    session_start();
    require 'php/vendor/autoload.php';
    use Google\Cloud\PubSub\PubSubClient;

/**
 * Publishes a message for a Pub/Sub topic.
 *
 * @param string $projectId  The Google project ID.
 * @param string $topicName  The Pub/Sub topic name.
 * @param string $message  The message to publish.
 */
    $count=1;
    $topicName='mytopic';
    $projectId='cloudlab3-249301';
    $message='Sample test : '.$count;
    $pubsub = new PubSubClient([
                               'projectId' => $projectId,
                               ]);
    $topic = $pubsub->topic($topicName);
    $topic->publish(['data' => $message]);
    echo "Message Published";
    
?>
<html>
<body>
<?php
    echo "This is the pubsub page sample";
?>
<form action="/main.php" method="post">
<button type="submit">Go Back</button>
</form>
<form action="" method="post">
<button type="submit">Publish Message</button>
</form>
<form action="subscribe.php" method="post">
<button type="submit">Subcribe</button>
</form>
</body>
</html>
