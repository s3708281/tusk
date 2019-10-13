<?php
    session_start();
    require 'php/vendor/autoload.php';
    use Google\Cloud\BigQuery\BigQueryClient;
    use Google\Cloud\PubSub\PubSubClient;
    use \google\appengine\api\mail\Message;
    $userid=$_SESSION['userid'];
    $orderid=$_SESSION['orderid'];
?>
<html>
<style>
table, td, th {
border: 1px solid black;
border-collapse: separate;
}
}
html
{
    background-image: url("https://storage.cloud.google.com/s3811346-storage/CueSa0.jpg");
width: 100%;
    background-color: #cccccc;
}
body {font-family: Arial, Helvetica, sans-serif; position: relative;
    background-image: url("https://storage.cloud.google.com/s3811346-storage/CueSa0.jpg");
width: 100%;
    background-color: #cccccc;
}
* {box-sizing: border-box;}
label-container
{
    color : white;
    position : fixed;
    right : 100%;
}
</style>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
<link type="text/css" rel="stylesheet" href="/bootstrap/css/bootstrap.css">
<link type="text/css" rel="stylesheet" href="/bootstrap/css/bootstrap-responsive.css">
<meta name="viewport" content="width=device-width, initial-scale=1"><body>
<nav class="navbar navbar-expand-sm bg-light navbar-light fixed-right">
<ul class="navbar-nav">
<li class="nav-item">
<a class="nav-link" href="#section1">Cart</a>
</li>
<li class="nav-item">
<a class="nav-link" href="#section2">E-mail Order Details</a>
</li>
<li class="nav-item">
<a class="nav-link" href="#section3">Contact Us</a>
</li>
<li class="nav-item">
<a class="nav-link" href="/customer.php">Go Back</a>
</li>
</li>
<li class="nav-item">
<a class="nav-link" href="/login.php">Logout</a>
</li>
</ul>
</nav>
<div id="section1"  style="padding-top:70px;padding-bottom:70px; color : white">
<div class="container">
  <div class="card bg-dark text-white">
<div class="card-body">
<?php
        $projectId='cloudlab3-249301';
        $datasetId='Testing';
        $bigQuery = new BigQueryClient([
                                   'projectId' => $projectId,
                                   ]);
    $dataset = $bigQuery->dataset($datasetId);
    $query="SELECT item_description, od.item_price as item_price,od.quantity as quantity FROM `Testing.order_details` as od, `Testing.item` as it where order_id=$orderid and it.item_id=od.item_id";
    $queryJobConfig = $bigQuery->query($query);
    $queryResults = $bigQuery->runQuery($queryJobConfig);
    $sum=0;
    $price=0;
    $msg=" ";
    $str="<table><tr><th>Item~~~~~~~~~~~~~~~~~~~~~~~</th>"."<th>Quantity~~~~~~~~~~~~~~~~~~~~~~~/th>"."<th>Price</th>"."<br></tr>";
    $msg.="Your Order id is :".$orderid;
    if($queryResults->isComplete())
    {
        foreach ($queryResults as $row)
        {
            $str.= "<tr><td>".$row['item_description']."</td><td>".$row['quantity']."</td><td>".$row['item_price']."</td></tr>";
            $price=$row['item_price']*$row['quantity'];
            $sum=$sum+$price;
        }
        
    }
    $str.="<tr><td>"."Total</td>"."<td>".$sum."</td></tr></table>";
    $msg.="Total Cost :".$sum;
    echo $str;
    $query="SELECT sum(item_price) as sum FROM `Testing.order_details` where order_id=$orderid";
    $queryJobConfig = $bigQuery->query($query);
    $queryResults = $bigQuery->runQuery($queryJobConfig);
    if($queryResults->isComplete())
    {
        foreach($queryResults as $total)
        $sum=$total['sum'];
    }
    $message="Your Order Details :      ".$msg."     "."Total Cost :     ".$sum;
    $topicName='mytopic';
    $pubsub = new PubSubClient([
                               'projectId' => $projectId,
                               ]);
    $topic = $pubsub->topic($topicName);
    $topic->publish(['data' => $message]);
    ?>
<form action=""  method="post">
<button type="submit" name="placeorder" class="btn btn-success">Place Order</button>
<button type="submit" name="cancel" class="btn btn-success">Cancel</button>
</form>
</div>
</div>
</div>
</div>
<?php
    if(isset($_POST['cancel']))
    {
        $mssg=" ";
        $topicName='mytopic';
        $pubsub = new PubSubClient([
                                   'projectId' => $projectId,
                                   ]);
        $topic = $pubsub->topic($topicName);
        $topic->publish(['data' => $message]);
        $subscription = $pubsub->subscription($subscriptionName);
        foreach ($subscription->pull() as $messages) {
            $mssg.=$messages->data();
            // Acknowledge the Pub/Sub message has been received, so it will not be pulled multiple times.
        }
        $adminsubject='Cart Cancellation';
        $adminemail='bhavi.smehta@gmail.com';
        $adminmessage = new Message();
        $adminmessage->setSender("bhavi.smehta@gmail.com");
        $adminmessage->addTo($adminemail);
        $adminmessage->setSubject($adminsubject);
        $adminmessage->setTextBody($mssg);
        $adminmessage->send();
        
        header("location:customer.php");
    }
    if(isset($_POST['placeorder']))
    {
        $userid=$_SESSION['userid'];
        $query="SELECT email from `Testing.customer_info` where user_id=$userid;";
        $queryJobConfig = $bigQuery->query($query);
        $queryResults = $bigQuery->runQuery($queryJobConfig);
        if($queryResults->isComplete())
        {
            foreach ($queryResults as $email)
            $mail = $email['email'];
        }
        
        $query="SELECT CURRENT_TIMESTAMP() as now;";
        $queryJobConfig = $bigQuery->query($query);
        $queryResults = $bigQuery->runQuery($queryJobConfig);
        if($queryResults->isComplete())
        {
            foreach ($queryResults as $timestamp)
            $datetime=$timestamp['now'];
        }
        
        $datetime=rtrim($datetime,"+00:00");
        $status="Open";
        $sql="INSERT INTO `orders` (`order_id`,`user_id`,`total_price`,`created_date`,`status`) VALUES ($orderid,$userid,$sum,'$datetime','$status')";
        $queryConfig = $bigQuery->query($sql)->defaultDataset($dataset); $response=$bigQuery->runQuery($queryConfig);
        if($response->isComplete()) {
            
            $subscriptionName='mytopic';
            $pubsub = new PubSubClient([
                                       'projectId' => $projectId,
                                       ]);
            $subscription = $pubsub->subscription($subscriptionName);
            foreach ($subscription->pull() as $messages) {
                $mssg2=$messages->data();
                // Acknowledge the Pub/Sub message has been received, so it will not be pulled multiple times.
            }
            
            $customersubject='Coffee Shop Order Placed';
            $customeremail=$mail;
            $customermessage = new Message();
            $customermessage->setSender("bhavi.smehta@gmail.com");
            $customermessage->addTo($customeremail);
            $customermessage->setSubject($customersubject);
            $customermessage->setTextBody($mssg2);
            $customermessage->send();
            
            $adminsubject='Coffee Shop New Order Received';
            $adminemail='bhavi.smehta@gmail.com';
            $adminmessage = new Message();
            $adminmessage->setSender("bhavi.smehta@gmail.com");
            $adminmessage->addTo($adminemail);
            $adminmessage->setSubject($adminsubject);
            $adminmessage->setTextBody($mssg2);
            $adminmessage->send();
            
            ?>
<div class="alert alert-success">
<strong>Order has been placed</strong><br>Order id : <?php echo $orderid; ?>
</div>
<?php
    }
    }
    ?>
<div id="section2"  style="padding-top:70px;padding-bottom:70px; color : white; border-top: double;">
<div class="container">
<form action="" class="form-container" method="post" style="position : absolute;">
<input type="email" name="mail" style="color: black" placeholder="Ex: abc@xyz.com">
<button type="submit" name="email" class="btn btn-primary">E-mail the details</button>
</form>
<?php
    if(isset($_POST['email']))
    {
        $subscriptionName='mytopic';
        $pubsub = new PubSubClient([
                                   'projectId' => $projectId,
                                   ]);
        $subscription = $pubsub->subscription($subscriptionName);
        foreach ($subscription->pull() as $messages) {
            $mssg1=$messages->data();
            // Acknowledge the Pub/Sub message has been received, so it will not be pulled multiple times.
        }
        
        $customersubject='Order Placed';
        $customeremail=$_POST['mail'];
        $customermessage = new Message();
        $customermessage->setSender("bhavi.smehta@gmail.com");
        $customermessage->addTo($customeremail);
        $customermessage->setSubject($customersubject);
        $customermessage->setTextBody($mssg1);
        $customermessage->send();
        ?>
<div class="alert alert-success" style="top : 0; position : relative;">
<strong>E-mail has been sent!</strong><br>
</div>
    <?php
        
        }
    ?>
</div>
</div>
<div id="section3"  style="padding-top:70px;padding-bottom:70px; color : white; border-top: double;">
<p>Need more help?</p>
<p><span class="glyphicon glyphicon-map-marker"></span>Melbourne, Australia</p>
<p><span class="glyphicon glyphicon-phone"></span>Phone: +61 0424 000 000</p>
<p><span class="glyphicon glyphicon-envelope"></span>Email: mail@mail.com</p>
</div>
</body>
</html>
