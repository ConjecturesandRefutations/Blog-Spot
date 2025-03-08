<?php

require_once realpath(__DIR__ . '/../config/base_path.php');
require_once realpath(__DIR__ . '/../config/db_connect.php');

require_once realpath(__DIR__ . '/../utilities/notifications/fetch_message_notifications.php');

if (session_status() == PHP_SESSION_NONE) {
    // Start the session only if it's not already started
    session_start();
}

$user = null; // Initialize the $user variable

if (isset($_SESSION["user_id"])) {

  $loggedInUserId = $_SESSION["user_id"];

  $messageNotifications = fetchMessageNotifications($loggedInUserId, $mysqli); // Fetch message notifications

  $totalMessageNotifications = count($messageNotifications); // Count message notifications

    $sql = "SELECT * FROM user WHERE user_id = {$_SESSION["user_id"]}";
    $result = $mysqli->query($sql);
    $user = $result->fetch_assoc();
}

// Display the message only if the user is logged out
if ($user === null) {
    echo '<div class="login-advice row" id="login-advice">';
    echo '<div class="col s12">';
    echo '<div class="card red lighten-3">';
    echo '<div class="card-content">';
    echo '<p class="center grey-text text-darken-3" style="font-weight: bold;">';
    echo 'You are in browsing mode. You must LOGIN to create, edit, and delete your own blogs.';
    echo '</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

function getFirstWords($string, $word_limit) {
  $words = explode(' ', $string);
  if (count($words) <= $word_limit) {
      return $string; // If the content has fewer or equal words than the limit, return the whole string.
  } else {
      return implode(' ', array_slice($words, 0, $word_limit)) . '...'; // Return the first $word_limit words followed by ellipsis.
  }
} 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Spot</title>
    <meta name="description" content="Create engaging blogs effortlessly with my simple platform. No technical skills needed — focus on content, and I handle the rest. Start blogging today!" />
    <link rel="stylesheet" href="./styles.css">
    <link rel="shortcut icon" href="../images/favicon.png" type="image/svg+xml">
    <!-- Materialize CSS linked below -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <!-- font awesome -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.13/css/all.css" integrity="sha384-DNOHZ68U8hZfKXOrtjWvjxusGo9WQnrNx2sqG0tfsghAvtVlRW3tvkXWZh58N9jp" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!--Import Google Icon Font-->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!--Font Awesome-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script src="./tinymce/tinymce.min.js"></script>
</head>
<body class="grey lighten-4">
<nav class="white z-depth-0 header" id="header">
    <div class="container header-container">
    <a href="index.php" class="left brand-logo brand-text" id="brand">Blog Spot</a>
    <a href="index.php" class="left"><img src="./images/BS.png" alt="Blog Spot Brand Title" id="brand-image"/></a>
    <ul class="right">

    <span id='nav-desktop'>

    <?php if (basename($_SERVER['PHP_SELF']) !== 'all-users.php') : ?>
    <li><a href="all-users.php" class="profile btn green lighten-3 z-depth-0">Users</a></li>
    <?php endif; ?>

<?php if (isset($_SESSION["user_id"])) : ?>
    <?php $loggedInUserId = $_SESSION["user_id"]; ?>

    <?php if (basename($_SERVER['PHP_SELF']) !== 'profile.php' || (isset($_GET['id']) && $_GET['id'] != $loggedInUserId)) : ?>
        <li><a href="profile.php?id=<?php echo $loggedInUserId; ?>" class="profile btn pink lighten-4 z-depth-0">Profile</a></li>
    <?php endif; ?>

    <?php if (basename($_SERVER['PHP_SELF']) !== 'add.php') : ?>
        <li><a href="add.php" class="write btn brand z-depth-0">Write a Blog</a></li>
    <?php endif; ?>
<?php endif; ?>


  <li><a href="<?php echo $user ? 'authentication/logout.php' : 'authentication/login.php'; ?>" class="write btn z-depth-0 secondary"><?php echo $user ? 'LOGOUT' : 'LOGIN'; ?></a></li>   
    
    </span>
  <?php if (isset($_SESSION["user_id"])) : ?>

<li class="dropdown-all">
          <a class="menu-item dropdown-trigger" href="#!" id="message-notifications-dropdown" data-target="message-notifications-dropdown-list">
              <i class="material-icons bell <?php echo $totalMessageNotifications > 0 ? 'red-text' : 'bell-text'; ?>">mail</i>
              <span class="new" data-badge-caption="" style="<?php echo $totalMessageNotifications > 0 ? '' : 'display:none;'; ?>">
                  <?php echo $totalMessageNotifications; ?>
              </span>
          </a>
          <?php if ($totalMessageNotifications > 0): ?>
              <ul id="message-notifications-dropdown-list" class="dropdown-content">
                  <?php foreach ($messageNotifications as $notification): ?>
                      <li>
                      <a href="<?php echo BASE_URL; ?>conversation.php?user_id=<?php echo htmlspecialchars($notification['sender_id']); ?>">
                          New message from <?php echo htmlspecialchars($notification['name']); ?>
                      </a>
                          <hr/>
                      </li>
                  <?php endforeach; ?>
              </ul>
          <?php else: ?>
              <ul id="message-notifications-dropdown-list" class="dropdown-content">
                  <li class="no-notifications">No new messages</li>
                  <li class="see-all-messages"><a style="color: #64B5F6;" href="<?php echo htmlspecialchars(BASE_URL); ?>messages.php" aria-label="View all messages">All Messages</a></li>
                  </ul>
          <?php endif; ?>
      </li>

<?php endif; ?>

  </ul>

    <a href="#" data-target="mobile-nav" class="sidenav-trigger" id="burger-anchor"><img src="images/burger.png" alt="burger menu" class="fa fa-bars black-text" id="burger-img"></a>
    </div>


</nav>

<!-- Mobile Navigation -->
<ul class="sidenav grey lighten-2" id="mobile-nav">
  <li><a href="#" class="sidenav-close"><i class="fa fa-times"></i></a></li>

  <?php if (basename($_SERVER['PHP_SELF']) !== 'index.php') : ?>
    <li><a href="index.php" class="home btn orange lighten-3 z-depth-0">Home</a></li>
  <?php endif; ?>  

  <?php if (basename($_SERVER['PHP_SELF']) !== 'all-users.php') : ?>
    <li><a href="all-users.php" class="profile btn green lighten-3 z-depth-0">Users</a></li>
  <?php endif; ?>  

  <?php if (isset($_SESSION["user_id"])) : ?>
    <?php $loggedInUserId = $_SESSION["user_id"]; ?>

    <?php if (basename($_SERVER['PHP_SELF']) !== 'profile.php' || (isset($_GET['id']) && $_GET['id'] != $loggedInUserId)) : ?>
      <li><a href="profile.php?id=<?php echo $loggedInUserId; ?>" class="profile btn pink lighten-4 z-depth-0">Profile</a></li>
    <?php endif; ?>

    <?php if (basename($_SERVER['PHP_SELF']) !== 'add.php') : ?>
      <li><a href="add.php" class="write btn brand z-depth-0">Write a Blog</a></li>
    <?php endif; ?>
  <?php endif; ?>

  <li><a href="<?php echo $user ? 'authentication/logout.php' : 'authentication/login.php'; ?>" class="btn z-depth-0 secondary"><?php echo $user ? 'LOGOUT' : 'LOGIN'; ?></a></li>
</ul>

<script>
document.addEventListener('DOMContentLoaded', function () {

  var dropdowns = document.querySelectorAll('.dropdown-trigger');
      M.Dropdown.init(dropdowns, {
          coverTrigger: false,
          constrainWidth: false,
          closeOnClick: false
      });

    
    let elems = document.querySelectorAll('.sidenav');
    let instances = M.Sidenav.init(elems);
    
    let header = document.getElementById('header');
    let headerHeight = header.offsetHeight; // Get the height of the header
    let scrollYOffset = <?php echo isset($_SESSION["user_id"]) ? '0' : '94'; ?>; // Set the initial scroll offset based on user login status
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > scrollYOffset) {
            header.classList.add('sticky');
            document.body.style.paddingTop = headerHeight + 'px'; // Add padding to the top of the body equal to the height of the header
        } else {
            header.classList.remove('sticky');
            document.body.style.paddingTop = 0; // Remove the padding when the header is no longer sticky
        }
    });
});

function updateMessageNotifications(data) {
      const notifications = data.message_notifications;
      const messageBadge = document.querySelector("#message-notifications-dropdown .new");
      const messageDropdown = document.getElementById("message-notifications-dropdown-list");

      // Update badge and icon styling
      const messageIcon = document.querySelector("#message-notifications-dropdown i");
      if (notifications.length > 0) {
        messageIcon.classList.add("red-text");
        if (messageBadge) {
          messageBadge.textContent = notifications.length;
          messageBadge.style.display = "inline";
        }
      } else {
        messageIcon.classList.remove("red-text");
        if (messageBadge) messageBadge.style.display = "none";
      }

        // Build the dropdown content
        let html = "";
        if (notifications.length > 0) {
          notifications.forEach(notification => {
            html += `
              <li>
                <a href="<?php echo BASE_URL; ?>conversation.php?user_id=${notification.sender_id}">
                  New message from ${name}
                </a>
                <hr/>
              </li>
            `;
          });
        } else {
          html += `
            <li class="no-notifications">No new messages</li>
            <li style="border-top:1px solid grey" class="see-all-messages">
              <a style="color: #64B5F6" href="<?php echo BASE_URL; ?>messages.php" aria-label="View all messages">All Messages</a>
            </li>
          `;
        }
        messageDropdown.innerHTML = html;
      }

      document.addEventListener("click", function(event) {
    const target = event.target.closest(".see-all-messages a");
    if (target) {
        event.preventDefault(); // Prevent default to avoid potential conflicts
        window.location.href = target.href; // Force navigation
    }
});

</script>