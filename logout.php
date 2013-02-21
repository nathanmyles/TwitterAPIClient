<?php

// load and clear sessions
session_start();
session_destroy();

// redirect to page for connect to twitter
header('Location: index.php');