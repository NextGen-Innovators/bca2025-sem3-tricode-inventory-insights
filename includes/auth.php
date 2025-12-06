<?php
session_start();

// If store page → require store login
function checkLogin() {
    $currentFile = basename($_SERVER['SCRIPT_NAME']);

    // If in "store" folder → must have store_id
    if (strpos($_SERVER['REQUEST_URI'], '/store/') !== false) {
        if (!isset($_SESSION['store_id'])) {
            header("Location: ../login.php");
            exit();
        }
    }

    // If in "customer" folder → must have customer_id
    if (strpos($_SERVER['REQUEST_URI'], '/customer/') !== false) {
        if (!isset($_SESSION['customer_id'])) {
            header("Location: ../login.php");
            exit();
        }
    }
}
