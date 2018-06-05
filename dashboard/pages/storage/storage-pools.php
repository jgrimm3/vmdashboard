<?php
// If the SESSION has not been started, start it now
if (!isset($_SESSION)) {
    session_start();
}

// If there is no username, then we need to send them to the login
//if (!isset($_SESSION['username'])){
//  header('Location: login.php');
//}

// We are now going to grab any GET/POST data and put in in SESSION data, then clear it.
// This will prevent duplicatig actions when page is reloaded.
if (isset($_GET['action'])) {
    $_SESSION['action'] = $_GET['action'];
    $_SESSION['path'] = $_GET['path'];
    $_SESSION['pool'] = $_GET['pool'];
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}
require('../header.php');
require('../navbar.php');

$action = $_SESSION['action']; //grab the $action variable from $_SESSION
$pool = $_SESSION['pool'];
$msg = "";


if ($action == 'volume-delete') {
  $msg = $lv->storagevolume_delete( base64_decode($_SESSION['path']) ) ? 'Volume has been deleted successfully' : 'Cannot delete volume';
}

if ($action == 'pool-delete') {
  $res = $lv->get_storagepool_res($pool);
  $msg = $lv->storagepool_undefine($res) ? 'Pool has been removed successfully' : 'Cannot remove pool';
}

if ($action == 'pool-destroy') {
  $res = $lv->get_storagepool_res($pool);
  $msg = $lv->storagepool_destroy($res) ? 'Pool has been stopped successfully' : 'Cannot stop pool';
}

if ($action == 'pool-start') {
  $res = $lv->get_storagepool_res($pool);
  $msg = $lv->storagepool_create($res) ? 'Pool has been started successfully' : 'Cannot start pool';
}

//pool-xml not yet configured
if ($action == "pool-xml") {
  $poolname = "default";
  $info = $lv->get_storagepool_info($poolname);
  echo "<textarea>";
  echo $info['xml'];
  echo "</textarea>";
}

unset($_SESSION['action']); //Unset the Action Variable to prevent repeats of action on page reload
unset($_SESSION['path']);
unset($_SESSION['pool']);


//Check to see if the iso_uploads directory exists. If it does and ISO has been uploaded
if (file_exists('../uploads/iso_uploads')) {

  $upload_path = realpath('../uploads/iso_uploads'); //determine the actual filepath of iso_uploads on the server
  $filename = '../uploads/iso_uploads/*.iso'; //set filepath to use with glob to determine any filename that ends with .iso

  $directory = "../uploads/iso_uploads/";
  $files = glob($directory . "*.iso"); //check to see if any files with .iso exist
  if ($files) {

    $pools = $lv->get_storagepools();
    for ($i = 0; $i < sizeof($pools); $i++) {
      $info = $lv->get_storagepool_info($pools[$i]);
      if ($upload_path == $info['path']) {
        $iso_pool_exists = true;
      }
    }

    if (!$iso_pool_exists) {
      $xml = "
        <pool type='dir'>
          <name>iso_uploads</name>
          <target>
            <path>$upload_path</path>
            <permissions>
            </permissions>
          </target>
        </pool>";

      $ret = $lv->storagepool_define_xml($xml) ? "success" : "Cannot add storagepool: ".$lv->get_last_error();
      if ($ret == "success"){
        $res = $lv->get_storagepool_res('iso_uploads');
        $ret = $lv->storagepool_create($res) ? 'Pool has been started successfully' : 'Cannot start pool';
      }
    }
  }
}

?>

<script>
function volumeDeleteWarning(linkURL) {
  swal("Delete volume?", {
    buttons: ["Cancel", true],
  }).then((value) => {
    if (value == true){
    // Redirect the user
    window.location = linkURL;
  }

  });
  }
</script>



<div class="content">
  <div class="card">
    <?php
    $pools = $lv->get_storagepools();
    for ($i = 0; $i < sizeof($pools); $i++) {
      //get the pool resource to use with refreshing the pool data
      $res = $lv->get_storagepool_res($pools[$i]);
      //refreshing the data before displaying because ISOs were not refreshing automatically and also the Available data was not correct after adding volumes
      $msg = $lv->storagepool_refresh($res) ? "Pool has been refreshed" : "Error refreshing pool: ".$lv->get_last_error();
      //getting the pool information to display the data in a table
      $info = $lv->get_storagepool_info($pools[$i]);
      ?>
    <div class="row">
      <div class="col-md-3">
        <div class="card-header">
          <h5 class="card-title"> Storage Pool: <?php echo $pools[$i]; ?> </h5>
        </div>
        <div class="card-body">
          <?php $act = $info['active'] ? 'Active' : 'Inactive';
          if ($act == "Active")
          echo "<a href=\"storage-wizard-volumes.php?action=storage-pools&amp;pool=$pools[$i]&amp;subaction=volume-create\"><i class=\"fa fa-plus\"></i> Create new volume </a> <br/> <br />";
          echo "<strong>Pool Name:</strong> " . $pools[$i] . "<br />";
          echo "<strong>Activity:</strong> " . $act . "<br />";
          echo "<strong>State:</strong> " . $lv->translate_storagepool_state($info['state']) . "<br />";
          echo "<strong>Capacity:</strong> " . $lv->format_size($info['capacity'], 2) . "<br />";
          echo "<strong>Allocation:</strong> " . $lv->format_size($info['allocation'], 2) . "<br />";
          echo "<strong>Available:</strong> " . $lv->format_size($info['available'], 2) . "<br />";
          echo "<strong>Path:</strong> " . $info['path'] . "<br />";
          echo "<strong>Actions:</strong> ";
          if ($lv->translate_storagepool_state($info['state']) == "Running") {
            echo "<a href=\"?action=pool-destroy&amp;pool=$pools[$i]\">Stop</a>";
          }
          if ($lv->translate_storagepool_state($info['state']) != "Running") {
            echo "<a href=\"?action=pool-start&amp;pool=$pools[$i]\">Start</a>";
            echo "<a href=\"?action=pool-delete&amp;pool=$pools[$i]\"> | Remove</a>";
          }
          ?>
        </div>
      </div>

      <div class="col-md-9">
        <div class="card-body">
          <?php
          //sub table
          if ($info['volume_count'] > 0) {
            echo "<div class=\"table-responsive\">" .
              "<table class=\"table table-striped\">" .
              "<thead class=\"text-primary\">" .
              "<tr>" .
              "<th>File Name</th>" .
              "<th>Type</th>" .
              "<th>Capacity</th>" .
              "<th>Allocation</th>" .
              "<th>Path</th>" .
              "<th>Actions</th>" .
              "</tr>" .
              "</thead>" .
              "</tbody>";
            $tmp = $lv->storagepool_get_volume_information($pools[$i]);
            $tmp_keys = array_keys($tmp);
            for ($ii = 0; $ii < sizeof($tmp); $ii++) {
              $capacity = $lv->format_size($tmp[$tmp_keys[$ii]]['capacity'], 2);
                if ($capacity == 0)
                  continue; //used to not display directories
              $path = base64_encode($tmp[$tmp_keys[$ii]]['path']);
              echo "<tr>" .
                "<td>{$tmp_keys[$ii]}</td>" .
                "<td>{$lv->translate_volume_type($tmp[$tmp_keys[$ii]]['type'])}</td>" .
                "<td>$capacity</td>" .
                "<td>{$lv->format_size($tmp[$tmp_keys[$ii]]['allocation'], 2)}</td>" .
               "<td>{$tmp[$tmp_keys[$ii]]['path']}</td>" .
               "<td><a onclick=\"volumeDeleteWarning('?action=volume-delete&amp;path=$path')\" href=\"#\">Delete</a></td>" .
               "</tr>";
             }
             echo "</tbody></table></div>";
           }
           ?>
        </div>
      </div>

    </div> <!-- end row -->
    <hr>
    <?php } //ends the for loop for each storage pool ?>
  </div>
</div>




<?php
require('../footer.php');
?>