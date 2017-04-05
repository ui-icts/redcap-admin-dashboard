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
            <span class="fa fa-male"></span>&nbsp; Projects by User</a>
    </li>

    <li <?= $_REQUEST['tab'] == 1 ? "class=\"active\"" : "" ?> >
        <a href="index.php?tab=1">
            <span class="fa fa-folder"></span>&nbsp; Users by Project</a>
    </li>

    <li <?= $_REQUEST['tab'] == 2 ? "class=\"active\"" : "" ?> >
        <a href="index.php?tab=2">
            <span class="fa fa-flask"></span>&nbsp; Research Projects</a>
    </li>

<!--    <li --><?//= $_REQUEST['tab'] == 3 ? "class=\"active\"" : "" ?><!-- >-->
<!--        <a href="index.php?tab=3">-->
<!--            <span class="fa fa-folder-open"></span>&nbsp; All Projects</a>-->
<!--    </li>-->

    <li <?= $_REQUEST['tab'] == 4 ? "class=\"active\"" : "" ?> >
        <a href="index.php?tab=4">
            <span class="fa fa-key"></span>&nbsp; Passwords in Projects</a>
    </li>

    <li <?= $_REQUEST['tab'] == 5 ? "class=\"active\"" : "" ?> >
        <a href="index.php?tab=5">
            <span class="fa fa-key"></span>&nbsp; Passwords in Instruments</a>
    </li>

    <li <?= $_REQUEST['tab'] == 6 ? "class=\"active\"" : "" ?> >
        <a href="index.php?tab=6">
            <span class="fa fa-key"></span>&nbsp; Passwords in Fields</a>
    </li>
</ul>

