<?php
if (session_status() == PHP_SESSION_NONE) {
    // Start the session only if it's not already started
    session_start();
}
$user = null; // Initialize the $user variable

if (isset($_SESSION["user_id"])) {
    $mysqli = require __DIR__ . "/../config/db_connect.php";
    $sql = "SELECT * FROM user WHERE user_id = {$_SESSION["user_id"]}";
    $result = $mysqli->query($sql);
    $user = $result->fetch_assoc();
}

// Display the message only if the user is logged out
if ($user === null) {
    echo '<div class="row">';
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
?>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Spot</title>
    <link rel="stylesheet" href="./styles.css">
    <link rel="shortcut icon" href="images/favicon.png" type="image/svg+xml">
    <!-- Materialize CSS linked below -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <!-- font awesome -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.13/css/all.css" integrity="sha384-DNOHZ68U8hZfKXOrtjWvjxusGo9WQnrNx2sqG0tfsghAvtVlRW3tvkXWZh58N9jp" crossorigin="anonymous">
    <!--Import Google Icon Font-->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#content',
            plugins: 'bold italic underline',
            toolbar: 'undo redo | bold italic underline',
        });
    </script>

</head>
<body class="grey lighten-4">
<nav class="white z-depth-0 fixed header">
    <div class="container">
    <a href="index.php" class="left brand-logo brand-text" id="brand">Blog Spot</a>
    <a href="index.php" class="left"><img src="./images/BS.png" alt="Blog Spot Brand Title" id="brand-image"/></a>
    <ul id='nav-mobile' class="right">
    <?php if (basename($_SERVER['PHP_SELF']) !== 'all-users.php') : ?>
    <li><a href="all-users.php" class="profile btn green lighten-3 z-depth-0">All Users</a></li>
    <?php endif; ?>

    <?php if (isset($_SESSION["user_id"])) : ?>
      <?php if (basename($_SERVER['PHP_SELF']) !== 'profile.php') : ?>
          <li><a href="profile.php?id=<?php echo $_SESSION["user_id"]; ?>" class="profile btn pink lighten-4 z-depth-0">Profile</a></li>
      <?php endif; ?>

      <?php if (basename($_SERVER['PHP_SELF']) !== 'add.php') : ?>
          <li><a href="add.php" class="write btn brand z-depth-0">Write a Blog</a></li>
      <?php endif; ?>
    <?php endif; ?>

  <li><a href="<?php echo $user ? 'authentication/logout.php' : 'authentication/login.php'; ?>" class="write btn z-depth-0 secondary"><?php echo $user ? 'LOGOUT' : 'LOGIN'; ?></a></li>   </ul>
    
    <a href="#" data-target="mobile-nav" class="sidenav-trigger" id="burger-anchor"><img src="images/burger.png" alt="burger menu" class="fa fa-bars black-text" id="burger-img"></a>
    </div>
</nav>

<!-- Mobile Navigation -->
<ul class="sidenav grey lighten-2" id="mobile-nav">
  <li><a href="#" class="sidenav-close"><i class="fa fa-times"></i></a></li>
  <?php if (basename($_SERVER['PHP_SELF']) !== 'all-users.php') : ?>
    <li><a href="all-users.php" class="profile btn green lighten-3 z-depth-0">All Users</a></li>
    <?php endif; ?>  

    <?php if (isset($_SESSION["user_id"])) : ?>
      <?php if (basename($_SERVER['PHP_SELF']) !== 'profile.php') : ?>
          <li><a href="profile.php?id=<?php echo $_SESSION["user_id"]; ?>" class="profile btn pink lighten-4 z-depth-0">Profile</a></li>
      <?php endif; ?>

      <?php if (basename($_SERVER['PHP_SELF']) !== 'add.php') : ?>
          <li><a href="add.php" class="write btn brand z-depth-0">Write a Blog</a></li>
      <?php endif; ?>
    <?php endif; ?>

      <li><a href="<?php echo $user ? 'authentication/logout.php' : 'authentication/login.php'; ?>" class="btn z-depth-0 secondary"><?php echo $user ? 'LOGOUT' : 'LOGIN'; ?></a></li>
</ul>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var elems = document.querySelectorAll('.sidenav');
      var instances = M.Sidenav.init(elems);
    });

  </script>