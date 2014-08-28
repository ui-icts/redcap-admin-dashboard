<?php
/**
 * @file navigationTabs.php
 * @author Fred R. McClurg, University of Iowa
 * @date August 28, 2014
 * @version 1.0
 */

/**
 * @brief Displays the navigation tabs consistantly across all applications in suite
 */
?>

<ul class="nav nav-tabs" role="tablist">
  <li <?= ! isset( $_REQUEST['tab'] ) &&
          basename( $_SERVER['PHP_SELF'] ) == "index.php" ? "class=\"active\"" : "" ?> >
     <a href="index.php">Project by Owner</a>
  </li>

  <li <?= $_REQUEST['tab'] == 1 ? "class=\"active\"" : "" ?> >
     <a href="index.php?tab=1">Users by Project</a>
  </li>

  <li <?= $_REQUEST['tab'] == 2 ? "class=\"active\"" : "" ?> >
     <a href="index.php?tab=2">Research Projects</a>
  </li>

  <li <?= $_REQUEST['tab'] == 3 ? "class=\"active\"" : "" ?> >
     <a href="index.php?tab=3">Owner Project Summary</a>
  </li>

  <li <?= basename( $_SERVER['PHP_SELF'] ) == "projectSizeSummary.php" ? "class=\"active\"" : "" ?> >
     <a href="projectSizeSummary.php">Project Size Summary</a>
  </li>

  <li <?= $_REQUEST['tab'] == 4 ? "class=\"active\"" : "" ?> >
     <a href="index.php?tab=4">Power User Summary</a>
  </li>

  <li <?= $_REQUEST['tab'] == 5 ? "class=\"active\"" : "" ?> >
     <a href="index.php?tab=5">Power User Details</a>
  </li>

  <li>
     <a href="#">Users by Department</a>
  </li>

  <li>
     <a href="#">Email External Users</a>
  </li>
</ul>
