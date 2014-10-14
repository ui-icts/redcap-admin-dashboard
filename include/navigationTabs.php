<?php
/**
 * @file navigationTabs.php
 * @author Fred R. McClurg, University of Iowa
 * @date August 28, 2014
 * @version 1.1
 *
 * @brief Displays the navigation tabs consistantly across all applications in suite
 */
?>

<ul class="nav nav-tabs" role="tablist">
  <li <?= ! isset( $_REQUEST['tab'] ) &&
          basename( $_SERVER['PHP_SELF'] ) == "index.php" ? "class=\"active\"" : "" ?> >
     <a href="index.php">
        <span class="glyphicon glyphicon-user"></span>&nbsp; Project by Owner</a>
  </li>

  <li <?= $_REQUEST['tab'] == 1 ? "class=\"active\"" : "" ?> >
     <a href="index.php?tab=1">
        <span class="fa fa-male"></span>&nbsp; Users by Project </a>
  </li>

  <li <?= $_REQUEST['tab'] == 2 ? "class=\"active\"" : "" ?> >
     <a href="index.php?tab=2">
        <span class="fa fa-flask"></span>&nbsp; Research Projects</a>
  </li>

  <li <?= $_REQUEST['tab'] == 3 ? "class=\"active\"" : "" ?> >
     <a href="index.php?tab=3">
        <span class="glyphicon glyphicon-list-alt"></span>&nbsp; Project Owner Summary</a>
  </li>

  <li <?= basename( $_SERVER['PHP_SELF'] ) == "projectSizeSummary.php" ? "class=\"active\"" : "" ?> >
     <a href="projectSizeSummary.php">
        <span class="fa fa-tachometer"></span>&nbsp; Project Size Summary</a>
  </li>

  <li <?= $_REQUEST['tab'] == 4 ? "class=\"active\"" : "" ?> >
     <a href="index.php?tab=4">
        <span class="glyphicon glyphicon-th-large"></span>&nbsp; Power User Summary</a>
  </li>

  <li <?= $_REQUEST['tab'] == 5 ? "class=\"active\"" : "" ?> >
     <a href="index.php?tab=5">
        <span class="glyphicon glyphicon-th"></span>&nbsp; Power User Details</a>
  </li>

  <li class="disabled">
     <a href="#" title="Not Implemented">
        <span class="fa fa-sitemap"></span>&nbsp; Users by Department</a>
  </li>

  <li class="disabled">
     <a href="#" title="Not Implemented">
        <span class="fa fa-envelope-o"></span>&nbsp; Email External Users</a>
  </li>
</ul>
