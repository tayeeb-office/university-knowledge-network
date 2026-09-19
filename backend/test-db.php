<?php

require_once __DIR__ . "/config/database.php";

try {

    $db = getDatabaseConnection();

    echo "Database Connected Successfully";

} catch (Throwable $e) {

    echo $e->getMessage();

}

?>