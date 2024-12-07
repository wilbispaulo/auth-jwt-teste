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

// COOKIES
define('OPTIONS',  [
    'path' => '/',
    'domain' => 'localhost',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Strict'
]);

// PATH
define('PDF_IMG_FILE_FR', ROOT . "/assets/images/carteira/cart_cat_2024_FR.jpg");
define('PDF_IMG_FILE_VR', ROOT . "/assets/images/carteira/cart_cat_2024_VR.jpg");
define('PDF_CRPB_FILE', ROOT . "/assets/images/crpb/crpb_2024.jpg");
