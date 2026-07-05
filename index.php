<?php
session_start();
if (isset($_SESSION['admin'])) {
    header('Location: admin/dashboard.php');
} else {
    header('Location: admin/login.php');
}
exit;
