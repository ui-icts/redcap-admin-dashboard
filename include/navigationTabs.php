<?php
/**
 * @file navigationTabs.php
 * @author Fred R. McClurg, University of Iowa
 * @date August 28, 2014
 * @version 1.2
 *
 * @brief Displays the navigation tabs consistently across all applications in suite
 */
?>

<ul class="nav nav-tabs" role="tablist">
  <li <?= $_REQUEST['tab'] == 0 &&
          basename( $_SERVER['PHP_SELF'] ) == "index.php" ? "class=\"active\"" : "" ?> >
     <a href="index.php">
         <span class="fa fa-male"></span>&nbsp; Users by Project </a>
  </li>

  <li <?= $_REQUEST['tab'] == 2 ? "class=\"active\"" : "" ?> >
     <a href="index.php?tab=2">
        <span class="fa fa-flask"></span>&nbsp; Research Projects</a>
  </li>

  <li <?= $_REQUEST['tab'] == 8 ? "class=\"active\"" : "" ?> >
     <a href="index.php?tab=8">
        <span class="fa fa-key"></span>&nbsp; Password in Projects</a>
  </li>

  <li <?= $_REQUEST['tab'] == 9 ? "class=\"active\"" : "" ?> >
     <a href="index.php?tab=9">
        <span class="fa fa-key"></span>&nbsp; Password in Instruments</a>
  </li>

  <li <?= $_REQUEST['tab'] == 10 ? "class=\"active\"" : "" ?> >
     <a href="index.php?tab=10">
        <span class="fa fa-key"></span>&nbsp; Password in Fields</a>
  </li>
</ul>
