<?php
require('header.php');

$uuid = $_GET['uuid'];
$domName = $lv->domain_get_name_by_uuid($_GET['uuid']);
$dom = $lv->get_domain_object($domName);
$protocol = isset($_SERVER['HTTPS']) ? "https://" : "http://";
$url = $protocol . $_SERVER['HTTP_HOST'];
$page = basename($_SERVER['PHP_SELF']);


// Domain Actions
if ($action == 'domain-start') {
  $ret = $lv->domain_start($domName) ? "Domain has been started successfully" : 'Error while starting domain: '.$lv->get_last_error();
}

if ($action == 'domain-pause') {
  $ret = $lv->domain_suspend($domName) ? "Domain has been paused successfully" : 'Error while pausing domain: '.$lv->get_last_error();
}

if ($action == 'domain-resume') {
  $ret = $lv->domain_resume($domName) ? "Domain has been resumed successfully" : 'Error while resuming domain: '.$lv->get_last_error();
}

if ($action == 'domain-stop') {
  $ret = $lv->domain_shutdown($domName) ? "Domain has been stopped successfully" : 'Error while stopping domain: '.$lv->get_last_error();
  $actioninfo = $lv->domain_get_info($dom);
  $actionstate = $lv->domain_state_translate($actioninfo['state']);
  if ($actionstate == "running"){
    $ret = "Domain is unable to shutdown gracefully. It will need to be forcefully turned off";
  }
}

if ($action == 'domain-destroy') {
  $ret = $lv->domain_destroy($domName) ? "Domain has been destroyed successfully" : 'Error while destroying domain: '.$lv->get_last_error();
}

if ($action == 'domain-delete') {
  $ret = $lv->domain_undefine($domName) ? "" : 'Error while deleting domain: '.$lv->get_last_error();
  if (!$lv->domain_get_name_by_uuid($uuid))
    header('Location: domain-list.php');
}


//Disk Actions
if ($action == 'domain-disk-remove') {
  $dev = $_GET['dev'];
  $ret = $lv->domain_disk_remove($domName, $dev) ? 'Disk has been removed successfully' : 'Cannot remove disk: '.$lv->get_last_error();
}

//Network Actions
if ($action == 'domain-nic-remove') {
  $mac = base64_decode($_GET['mac']);
  $ret = $lv->domain_nic_remove($domName, $mac) ? "Network device successfully removed" : 'Error while removing network device: '.$lv->get_last_error();
}

//Snapshot Actions
if ($action == 'domain-snapshot-create') {
  $msg = $lv->domain_snapshot_create($domName) ? "Snapshot for $domName successfully created" : 'Error while taking snapshot of domain: '.$lv->get_last_error();
}

if ($action == 'domain-snapshot-delete') {
  $snapshot = $_GET['snapshot'];
  $msg = $lv->domain_snapshot_delete($domName, $snapshot) ? "Snapshot $snapshot for $domName successfully deleted" : 'Error while deleting snapshot of domain: '.$lv->get_last_error();
}

if ($action == 'domain-snapshot-revert') {
  $snapshot = $_GET['snapshot'];
  $msg = $lv->domain_snapshot_revert($domName, $snapshot) ? "Snapshot $snapshot for $domName successfully applied" : 'Error while reverting snapshot of domain: '.$lv->get_last_error();
}

if ($action == 'domain-snapshot-xml') {
  $snapshot = $_GET['snapshot'];
  $snapshotxml = $lv->domain_snapshot_get_xml($domName, $snapshot);
  //Parsing the snapshot XML file - in Ubuntu requires the php-xml package
  $xml = simplexml_load_string($snapshotxml);
  //Alternative way to parse
  //$xml = new SimpleXMLElement($snapshotxml);
}

if ($action == 'domain-edit') {
  if (@$_POST['xmldesc']) {
    $ret = $lv->domain_change_xml($domName, $_POST['xmldesc']) ? "Domain definition has been changed" : 'Error changing domain definition: '.$lv->get_last_error();
  }
  header("Location: $page?uuid=$uuid");
}

//get info, mem, cpu, state, id, arch, and vnc after actions to reflect any changes to domain
$info = $lv->domain_get_info($dom);
$mem = number_format($info['memory'] / 1048576, 2, '.', ' ').' GB';
$cpu = $info['nrVirtCpu'];
$state = $lv->domain_state_translate($info['state']);
$id = $lv->domain_get_id($dom);
$arch = $lv->domain_get_arch($dom);
$vnc = $lv->domain_get_vnc_port($dom);

if (!$id)
  $id = 'N/A';
if ($vnc <= 0)
	$vnc = 'N/A';

require('navigation.php');


// Setting up VNC connection information. tokens.list needs to have www-data ownership or 777 permissions
$liststring = "";
$listarray = $lv->get_domains();
foreach ($listarray as $listname) {
  $listdom = $lv->get_domain_object($listname);
  $listinfo = libvirt_domain_get_info($listdom);
  //Don't use $lv->domain_get_info($listdom) because the state is cached and caused delay state status
  $liststate = $lv->domain_state_translate($listinfo['state']);
  if ($liststate == "running") {
    $listdomuuid = libvirt_domain_get_uuid_string($listdom);
    $listvnc = $lv->domain_get_vnc_port($listdom);
    $liststring = $liststring . $listdomuuid . ": " . "localhost:" . $listvnc . "\n";
  }
}
$listfile = "../tokens.list";
$list = file_put_contents($listfile, $liststring);
?>




<!-- page content -->
<div class="right_col" role="main">
  <div class="">
    <div class="page-title">
      <div class="title_left">
        <h3>Domain Information</h3>
      </div>
    </div>

    <div class="clearfix"></div>

    <div class="row">
      <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
          <div class="x_title">
            <h2><a href="?uuid=<?php echo $uuid; ?>"><?php echo $domName; ?></a></h2>
            <ul class="nav navbar-right panel_toolbox">
              <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
              </li>
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                <ul class="dropdown-menu" role="menu">
                  <li><a href="#">Settings 1</a>
                  </li>
                  <li><a href="#">Settings 2</a>
                  </li>
                </ul>
              </li>
              <li><a class="close-link"><i class="fa fa-close"></i></a>
              </li>
            </ul>
            <div class="clearfix"></div>
          </div>
          <div class="x_content">
            <div class="col-md-4 col-sm-4 col-xs-12 profile_left">
              <div class="profile_img">
                <div id="crop-avatar">
                  <!-- Current avatar -->
                  <?php
                  if ($state == "running") {
                    //screenshot will get raw png data at 300 pixels wide
                    $screenshot = $lv->domain_get_screenshot_thumbnail($_GET['uuid'], 400);
                    //the raw png data needs to be encoded to use with html img tag
                    $screen64 = base64_encode($screenshot['data']);
                    ?>
                    <a href="<?php echo $url; ?>:6080/vnc_lite.html?path=?token=<?php echo $uuid ?>" target="_blank">
                    <img src="data:image/png;base64,<?php echo $screen64 ?>" width="300px"/>
                    </a>
                    <?php
                  } else if ($state == "paused") {
                    echo "<img src='assets/img/paused.png' width='300px' >";
                  } else {
                    echo "<img src='assets/img/shutdown.png' width='300px' >";
                  }
                  ?>
              <!--    <img class="img-responsive avatar-view" src="images/picture.jpg" alt="Avatar" title="Change the avatar"> -->
                </div>
              </div>
              <h4>General Information</h4>

              <ul class="list-unstyled user_data">

                <?php
                /* General information */
                echo "<li><strong>Domain type: </strong>".$lv->get_domain_type($domName)."</li>";
                echo "<li><strong>Domain emulator: </strong>".$lv->get_domain_emulator($domName)."</li>";
                echo "<li><strong>Domain memory: </strong>$mem</li>";
                echo "<li><strong>Number of vCPUs: </strong>$cpu</li>";
                echo "<li><strong>Domain state: </b>$state</li>";
                echo "<li><strong>Domain architecture: </strong>$arch</li>";
                echo "<li><strong>Domain ID: </strong>$id</li>";
                echo "<li><strong>VNC Port: </strong>$vnc</li>";
                echo '<br/>';
                if ($die)
                  die('</body></html');
                echo "<br />";
                ?>

              </ul>


              <!-- start actions -->
              <h4>Actions</h4>
              <ul class="list-unstyled user_data">

                <?php  if ($state == "running") { ?>
                  <li><a href="<?php echo $url; ?>:6080/vnc_lite.html?path=?token=<?php echo $uuid; ?>" target="_blank" >
                    <i class="fas fa-desktop"></i> VNC Connection<br />
                  </a></li>
                <?php } ?>

                <?php if ($state == "shutoff") { ?>
                  <li><a href="?action=domain-start&amp;uuid=<?php echo $uuid; ?>" target="_self" >
                    <i class="fas fa-power-off"></i> Power guest on<br />
                  </a></li>
                <?php } ?>

                <?php  if ($state == "running") { ?>
                  <li><a href="?action=domain-stop&amp;uuid=<?php echo $uuid; ?>" target="_self" >
                  <i class="fas fa-power-off"></i> Power guest off<br />
                </a></li>
                  <li><a href="?action=domain-pause&amp;uuid=<?php echo $uuid; ?>" target="_self" >
                    <i class="fas fa-pause"></i> Pause guest<br />
                  </a></li>
                <?php } ?>

                <?php  if ($state == "paused") { ?>
                  <li><a href="?action=domain-resume&amp;uuid=<?php echo $uuid; ?>" target="_self" >
                    <i class="fas fa-play"></i> Resume guest<br />
                  </a></li>
                <?php } ?>

                <?php  if ($state != "shutoff") { ?>
                  <li><a href="?action=domain-destroy&amp;uuid=<?php echo $uuid; ?>" target="_self" >
                    <i class="fas fa-plug"></i> Turn off<br />
                  </a></li>
                <?php } ?>

                <?php  if ($state == "shutoff") { ?>
                  <li><a onclick="domainDeleteWarning('?action=domain-delete&amp;uuid=<?php echo $_GET['uuid'] ?>')" href="#">
                    <i class="fas fa-trash"></i> Delete guest<br />
                  </a></li>
                <?php } ?>
              </ul>
              <!-- end of actions -->

            </div>

            <!-- next column -->
            <div class="col-md-9 col-sm-9 col-xs-12">


              <div class="" role="tabpanel" data-example-id="togglable-tabs">
                <ul id="myTab" class="nav nav-tabs bar_tabs" role="tablist">
                  <li role="presentation" class="active"><a href="#tab_content1" id="storage-tab" role="tab" data-toggle="tab" aria-expanded="true">Storage</a>
                  </li>
                  <li role="presentation" class=""><a href="#tab_content2" role="tab" id="networking-tab" data-toggle="tab" aria-expanded="false">Networking</a>
                  </li>
                  <li role="presentation" class=""><a href="#tab_content3" role="tab" id="snapshots-tab" data-toggle="tab" aria-expanded="false">Snapshots</a>
                  </li>
                  <li role="presentation" class=""><a href="#tab_content4" role="tab" id="xml-tab" data-toggle="tab" aria-expanded="false">XML</a>
                  </li>
                </ul>
                <div id="myTabContent" class="tab-content">
                  <div role="tabpanel" class="tab-pane fade active in" id="tab_content1" aria-labelledby="storage-tab">
                    <?php
                    /* Disk information */
                    echo "<a title='Add new disk' href=guest-disk-wizard.php?action=domain-disk-add&amp;uuid=" . $uuid . "><i class='fas fa-plus'></i> Add new disk </a><br />";
                    $tmp = $lv->get_disk_stats($domName);
                    if (!empty($tmp)) {
                      echo "<div class='table-responsive'>" .
                        "<table class='table'>" .
                        "<tr>" .
                        "<th>Disk storage</th>" .
                        "<th>Storage driver type</th>" .
                        "<th>Domain device</th>" .
                        "<th>Disk capacity</th>" .
                        "<th>Disk allocation</th>" .
                        "<th>Physical disk size</th>" .
                        "<th>Actions</th>" .
                        "</tr>" .
                        "<tbody>";
                      for ($i = 0; $i < sizeof($tmp); $i++) {
                        $capacity = $lv->format_size($tmp[$i]['capacity'], 2);
                        $allocation = $lv->format_size($tmp[$i]['allocation'], 2);
                        $physical = $lv->format_size($tmp[$i]['physical'], 2);
                        $dev = (array_key_exists('file', $tmp[$i])) ? $tmp[$i]['file'] : $tmp[$i]['partition'];
                        echo "<tr>" .
                          "<td>".basename($dev)."</td>" .
                          "<td>{$tmp[$i]['type']}</td>" .
                          "<td>{$tmp[$i]['device']}</td>" .
                          "<td>$capacity</td>" .
                          "<td>$allocation</td>" .
                          "<td>$physical</td>" .
                          "<td>" .
                            "<a title='Remove disk device' onclick=\"diskRemoveWarning('?action=domain-disk-remove&amp;dev=" . $tmp[$i]['device'] . "&amp;uuid=" . $_GET['uuid'] . "')\" href='#'><i class='fas fa-trash-alt'></i></a>" .
                          "</td>" .
                          "</tr>";
                      }
                      echo "</tbody></table></div>";
                    } else {
                      echo "Domain doesn't have any disk devices";
                    }
                    ?>
                  </div>


                  <div role="tabpanel" class="tab-pane fade" id="tab_content2" aria-labelledby="networking-tab">
                    <?php
                    /* Network interface information */
                    echo "<a href=\"guest-network-wizard.php?uuid=$uuid\"><i class=\"fas fa-plus\"> </i> Add new network </a>";
                    $tmp = $lv->get_nic_info($domName);
                    if (!empty($tmp)) {
                      $anets = $lv->get_networks(VIR_NETWORKS_ACTIVE);
                      echo "<div class='table-responsive'>" .
                        "<table class='table'>" .
                        "<tr>" .
                        "<th>MAC Address</th>" .
                        "<th>NIC Type</th>" .
                        "<th>Network</th>" .
                        "<th>Network active</th>" .
                        "<th>Actions</th>" .
                        "</tr>" .
                        "<tbody>";
                      for ($i = 0; $i < sizeof($tmp); $i++) {
                        $mac_encoded = base64_encode($tmp[$i]['mac']); //used to send via $_GET
                        if (in_array($tmp[$i]['network'], $anets))
                          $netUp = 'Yes';
                        else
                          $netUp = 'No <a href="">[Start]</a>';
                        echo "<tr>" .
                          "<td>{$tmp[$i]['mac']}</td>" .
                          "<td>{$tmp[$i]['nic_type']}</td>" .
                          "<td>{$tmp[$i]['network']}</td>" .
                          "<td>$netUp</td>" .
                          "<td>" .
                            "<a href=\"?action=domain-nic-remove&amp;uuid={$_GET['uuid']}&amp;mac=$mac_encoded\">" .
                            "Remove network card</a>" .
                          "</td>" .
                          "</tr>";
                      }
                      echo "</tbody></table></div>";
                    } else {
                      echo '<p>Domain doesn\'t have any network devices</p>';
                    }
                    ?>
                  </div>


                  <div role="tabpanel" class="tab-pane fade" id="tab_content3" aria-labelledby="snapshots-tab">
                    <?php
                    /* Snapshot information */
                    echo "<h3>Snapshots</h3>";
                    echo "<a title='Create snapshot' href=?action=domain-snapshot-create&amp;uuid=" . $_GET['uuid'] . "><i class='fas fa-plus'></i> Create new snapshot</a><br />";
                    $tmp = $lv->list_domain_snapshots($dom);
                    if (!empty($tmp)) {
                      echo "<div class='table-responsive'>" .
                        "<table class='table'>" .
                        //"<thead class='text-primary'>" .
                        "<tr>" .
                        "<th>Name</th>" .
                        "<th>Creation Time</th>" .
                        "<th>State</th>" .
                        "<th>Actions</th>" .
                        "</tr>" .
                        //"</thead>" .
                        "<tbody>";

                      foreach ($tmp as $key => $value) {
                        //Getting XML info on the snapshot. Using simpleXLM because libvirt xml functions don't seem to work for snapshots
                        $tmpsnapshotxml = $lv->domain_snapshot_get_xml($domName, $value);
                        $tmpxml = simplexml_load_string($tmpsnapshotxml);
                        $name = $tmpxml->name[0];
                        $creationTime = $tmpxml->creationTime[0];
                        $snapstate = $tmpxml->state[0];
                        echo "<tr>";
                        echo "<td>" . $name . "</td>";
                        echo "<td>" . date("D d M Y", $value) . " - ";
                        echo date("H:i:s", $value) . "</td>";
                        echo "<td>" . $snapstate . "</td>";
                        echo "<td>
                          <a title='Delete snapshot' onclick=\"snapshotDeleteWarning('?action=domain-snapshot-delete&amp;snapshot=" . $value . "&amp;uuid=" . $_GET['uuid'] . "')\" href='#'><i class='fas fa-trash-alt'></i></a>
                          <a title='Revert snapshot' href=?action=domain-snapshot-revert&amp;uuid=" . $_GET['uuid'] . "&amp;snapshot=" . $value . "><i class='fas fa-exchange-alt'></i></a>
                          <a title='Snapshot XML' href=?action=domain-snapshot-xml&amp;uuid=" . $_GET['uuid'] . "&amp;snapshot=" . $value . "><i class='fas fa-code'></i></a>
                          </td>";
                        echo "</tr>";
                      }
                      echo "</tbody></table></div>";
                    } else {
                      echo "Domain does not have any snapshots";
                    }

                    if ($snapshotxml != null) {
                      echo "<hr>";
                      echo "<h3>Snapshot XML: " . $snapshot . "</h3>";
                      echo  "<textarea rows=15 cols=50>" . $snapshotxml . "</textarea>";
                    }
                     ?>
                  </div>

                  <div role="tabpanel" class="tab-pane fade" id="tab_content4" aria-labelledby="xml-tab">
                    <?php
                    /* XML information */
                    $inactive = (!$lv->domain_is_running($domName)) ? true : false;
                    $xml = $lv->domain_get_xml($domName, $inactive);
                    $ret = htmlentities($xml);


                    if ($state == "shutoff"){
                      $ret = "<form method=\"POST\" action=?action=domain-edit&amp;uuid=" . $_GET['uuid'] . " >" .
                        "<textarea name=\"xmldesc\" rows=\"17\" cols=\"2\" style=\"width: 100%; margin: 0; padding: 0; border-width: 0; background-color:#ebecf1;\" >" . $xml . "</textarea>" .
                        "<br /> <br /> <input type=\"submit\" value=\"Save XML\"></form>";
                      echo $ret;
                    } else {
                      echo "Editing XML is performed when virtual guest is shutoff <br />";
                      echo "<textarea rows=\"17\" cols=\"2\" style=\"width: 100%; margin: 0; padding: 0; border-width: 0;\" readonly>" . $ret . "</textarea>";
                    }
                    ?>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- /page content -->









<?php
require('footer.php');
?>


<script>

function domainDeleteWarning(linkURL) {
  swal({
    title: 'Are you sure?',
    text: 'This will delete the virtual machine configuration',
    type: 'warning',
    confirmButtonText: 'Yes, delete it!',
    showCancelButton: true
  }).then(function($result) {
    // Redirect the user
    window.location = linkURL;
  });
}

function diskRemoveWarning(linkURL) {
  swal({
    title: 'Are you sure?',
    text: 'This will remove the disk from the configuration',
    type: 'warning',
    confirmButtonText: 'Yes, remove it!',
    showCancelButton: true
  }).then(function($result) {
    // Redirect the user
    window.location = linkURL;
  });
}

function snapshotDeleteWarning(linkURL) {
  swal({
    title: 'Are you sure?',
    text: 'This will delete the snapshot',
    type: 'warning',
    confirmButtonText: 'Yes, delete it!',
    showCancelButton: true
  }).then(function($result) {
    // Redirect the user
    window.location = linkURL;
  });
}

</script>