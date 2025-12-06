<?php
function checkLogin() {
    if(!isset($_SESSION['store_id'])) {
        header("Location: ../login.php");
        exit();
    }
}

function getStoreInfo($conn) {
    if(isset($_SESSION['store_id'])) {
        $stmt = $conn->prepare("SELECT * FROM stores WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['store_id']);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    return null;
}
?>