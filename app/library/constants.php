<?php

// PATH
define("CONTROLLER_PATH", "app\\controllers\\");
define("ROOT", dirname(__FILE__, 3));
define("VIEWS", ROOT . "\\app\\views\\");
define("CSS", "\\assets\\css\\");
define("JS", "\\assets\\js\\");
define("IMG", "\\assets\\img\\");
define("CERT", ROOT . "\\core\\certificados\\");
define("HOST", "http://localhost");
define("LOGIN_API", HOST . "/login");
define("MAIN_LOCATION", HOST . "/");

//MAGIC NUMBERS
define('NOT_AUTH', 0);
define('EMAIL_OK', 1);
define('PASSWORD_OK', 2);
define('ALL_OK', 3);
define("EMAIL_NOREG", 0);
define("TKRS_INVALID", 1);
define("TKRS_EXPTIME", 2);
