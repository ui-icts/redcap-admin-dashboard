<?php
if (isset($_POST['action'])) {
    LogToConsole($_POST['action']);

    setcookie('test', 'testvalue', 0);


    if ($_COOKIE['test']) {
        LogToConsole($_COOKIE['test']);
    }
    else {
        setcookie('test', 'testvalue', 0);
    }
}

function select() {
    exit;
}

function insert() {
    echo "The insert function is called.";
    exit;
}
