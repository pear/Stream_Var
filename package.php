<?php
/**
 * script to automate the generation of the
 * package.xml file.
 *
 * $Id$
 *
 * @author      Stephan Schmidt <schst@php.net>
 * @package     Stream_Var
 * @subpackage  Tools
 */

/**
 * uses PackageFileManager
 */ 
require_once 'PEAR/PackageFileManager.php';

/**
 * current version
 */
$version = '1.0.0';

/**
 * current state
 */
$state = 'stable';

/**
 * release notes
 */
$notes = <<<EOT
- fixed bug #2579: warning at stream_open(),
- fixed notice in dir_opendir,
- bumped to version 1.0.0, according to version number guidelines
EOT;

/**
 * package description
 */
$description = <<<EOT
Stream_Var can be registered as a stream with stream_register_wrapper() and allows stream based acces to variables in any scope. Arrays are treated as directories, so it's possible to replace temporary directories and files in your application with variables.
EOT;

$package = new PEAR_PackageFileManager();

$result = $package->setOptions(array(
    'package'           => 'Stream_Var',
    'summary'           => 'Allows stream based access to any variable.',
    'description'       => $description,
    'version'           => $version,
    'state'             => $state,
    'license'           => 'PHP License',
    'filelistgenerator' => 'cvs',
    'ignore'            => array('package.php', 'package.xml'),
    'notes'             => $notes,
    'simpleoutput'      => true,
    'baseinstalldir'    => 'Stream',
    'packagedirectory'  => './',
    'dir_roles'         => array('docs' => 'doc',
                                 'examples' => 'doc',
                                 'tests' => 'test',
                                 )
    ));

if (PEAR::isError($result)) {
    echo $result->getMessage();
    die();
}

$package->addMaintainer('schst', 'lead', 'Stephan Schmidt', 'schst@php-tools.net');
$package->addDependency('php', '4.3.2', 'ge', 'php', false);

if (isset($_GET['make']) || (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] == 'make')) {
    $result = $package->writePackageFile();
} else {
    $result = $package->debugPackageFile();
}

if (PEAR::isError($result)) {
    echo $result->getMessage();
    die();
}
?>