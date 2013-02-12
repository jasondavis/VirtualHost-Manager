#!/usr/bin/php -q
<?php
/*
 * Define STDIN in case if it is not already
 * defined by PHP for some reason
 */
if(!defined("STDIN")) {
    define("STDIN", fopen('php://stdin','r'));
}


/*
 * Checks if the next arguments
 */
if ( preg_match( '/^\-\-php\=/' , $argv[1] ) ) {
    array_shift($argv);
}
else if (count($argv) == 0) exit;


/*
 * Checks if the folder exists ~/.vhm
 */

/*
 * Le data ~/.vhm/conf @TODO
 */



/*
 * Configuration Variables
 */
define("APACHE_PATH", '/Applications/MAMP/conf/apache');
define("APACHE_PATH_SITES", APACHE_PATH . '/sites-enabled/');
define("APACHE_PATH_WWW", '/Applications/MAMP/htdocs/');
define("HOSTS_PATH", '/etc/hosts');
define("APACHE_PORT", 80);


/*
 * MAC - Checks if there is a folder "sites-enabled" and include the "httpd.conf"
 */
if (PHP_OS == 'Darwin') {
    // "sites-enabled"
    if ( !file_exists( APACHE_PATH_SITES ) ) {
        mkdir( APACHE_PATH_SITES , 0755 );
    }
    
    // Include folder with vhosts
    $includeVhosts = "NameVirtualHost *:" . APACHE_PORT . "\n\n";
    $includeVhosts .= "# Include the virtual host configuration\n";
    $includeVhosts .= "Include " . APACHE_PATH_SITES . "\n\n";
    
    $conf = file_get_contents( APACHE_PATH . '/httpd.conf' );

    if ( !preg_match( "/ˆInclude " . preg_replace("/\//", "\/", APACHE_PATH_SITES) . "$/", $conf ) ) {
        file_put_contents( APACHE_PATH . '/httpd.conf', $conf . $includeVhosts );
    }
}


/*
 * Feedback
 */
$feedback = "";

/*
 * Se for nw | del | up
 */
switch ($argv[1]) {
    /*
     * Create vhost
     */
    case 'nw':
    case 'new': 
        new_vhost($argv[2]);
    break;
    
    /*
     * Delete vhost
     */
    case 'del':
    case 'delete':
        delete_vhost($argv[2]);
    break;
    
    /*
     * Restart Apache
     */
    case 'up':
        restart_apache();
    break;
}

/*
 * Imprime o Feedback
 */
echo $feedback;


/**
 * Cria o novo Virtual Host.
 */
function new_vhost( $name = NULL ) {
    
    $name = strtolower($name);
    
    /*
     * Create a folder on the Apache WWW
     */
    $vhPath = APACHE_PATH_WWW . $name;
    if ( !file_exists( $vhPath ) ) {
        mkdir( $vhPath , 0755 );
    }
    
    /*
     * Configuration VH
     */
    $vhConf = "<VirtualHost *:" . APACHE_PORT . ">";
    $vhConf .= "\n\tDocumentRoot " . $vhPath;
    $vhConf .= "\n\tServerName localhost." . $name;
    $vhConf .= "\n</VirtualHost>"; 
        
    /*
     * Writes the configuration file VH
     */
    sudo_write_file( APACHE_PATH_SITES . $name , $vhConf);
    
    
    /*
     * Reads the configuration file HOSTS
     */
    $hConf = file_get_contents( HOSTS_PATH );
    
    /*
     * Check existence
     */
    $hTitle = strtoupper($name);
    if ( !preg_match( "/" . $hTitle . "/" , $hConf ) ) {
        /*
         * or domain
         */
        hosts_configuration($hConf, $hTitle, $name);
        
        /*
         * Writes the configuration file HOSTS
         */
        sudo_write_file( HOSTS_PATH , $hConf );
    }
    
    /*
     * Creates index.html
     */
    $htmIndex = $vhPath . '/index.php';
    if (!file_exists($htmIndex)) {
        file_put_contents( $htmIndex , '<h1>Virtual Host criado com VHM !!!</h1>' );
    }
    
    /*
     * Restart Apache
     */
    restart_apache();
    
    /*
     * Feedback do proceso
     */
    global $feedback;
    $feedback .= "\n+-----------------------------------------------+\n";
    $feedback .= "|           Dados do Virtual Host               |\n";
    $feedback .= "+-----------------------------------------------+\n";
    $feedback .= " DocumentRoot: $vhPath\n";
    $feedback .= "          URL: http://localhost.$name\n";
    $feedback .= "+------------------------------------------+\n\n";
}


/**
 * Turn off or Virtual Host @TODO
 */
function delete_vhost( $name = NULL ) {
    
    $name = strtolower($name);
    
    /*
     * Clears the configuration VH
     */
    sudo_remove_file( APACHE_PATH_SITES . $name );
    
    /*
     * Clears configuration HOSTS
     */
    
}


/**
 * Restart Apache
 */
function restart_apache() {
    switch (PHP_OS) {
        case "Linux":
            system( "sudo service apache2 restart", $result );
        break;
        case "Darwin":
            system( "/Applications/MAMP/Library/bin/apachectl restart", $result );
        break;
    }
}


function hosts_configuration( &$hConf, $hTitle, $name ) {
    
    /*
     * Cria o domain
     */
    $hConf .= "\n\n## " . $hTitle . " ##\n";
    $hConf .= "127.0.0.1    localhost." . $name;
}

/**
 * Escreve no arquivo com permissão root
 */
function sudo_write_file( $path, $text) {

    exec( "echo '$text' | sudo tee $path", $result );

}

/**
 * Apaga arquivo com permissão root
 */
function sudo_remove_file( $path ) {
    
    exec( "sudo rm -rf $path", $result );
    
}
