<?php
// If the SESSION has not started, start it now
if (!isset($_SESSION)) {
    session_start();
}

// If there is no username, then we need to send them to the login
if (!isset($_SESSION['username'])){
  header('Location: ../login.php');
}


require('../header.php');
require('../navbar.php');
?>

<div class="content">
  <div class="card">
    <form action="" method="POST">
      <div class="card-header">
        <h4 class="card-title"> Test Page</h4>
      </div>
      <div class="card-body">
      <?php
      $info = $lv->host_get_node_info();
      $cpu = $lv->host_get_node_cpu_stats();
      $multi_cpu = $lv->host_get_node_cpu_stats_for_each_cpu();
      $mem = $lv->host_get_node_mem_stats();
      var_dump($info);
      echo "<br><br><br>";
      var_dump($cpu);
      echo "<br><br><br>";
      var_dump($multi_cpu);
      echo "<br><br><br>";
      var_dump($mem);
      ?>

      </div> <!-- end card body -->
      <div class="card-footer text-right">
        <input type="submit" class="btn btn-danger" name="action" value="Change" >
      </div>
    </form>
  </div> <!-- end card -->
</div> <!-- end content -->

<?php
require('../footer.php');
?>