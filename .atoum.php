<?php
/**
 * @var $runner \mageekguy\atoum\runner
 * @var $script \mageekguy\atoum\configurator
 */

use mageekguy\atoum;
use mageekguy\atoum\report\fields\runner\coverage;
use mageekguy\atoum\reports;
use mageekguy\atoum\reports\coverage as new_coverage;

define('PROJECT_NAME', 'kgestion');
define('TMP_DIR', __DIR__);
define('COVERAGE_TITLE', PROJECT_NAME);
define('COVERAGE_DIRECTORY', TMP_DIR . '/atoum/' . PROJECT_NAME . '_coverage');
//define('COVERAGE_DIRECTORY_OLD', TMP_DIR . '/atoum/' . PROJECT_NAME . '_coverage/old');
define('COVERAGE_WEB_PATH', 'file://' . COVERAGE_DIRECTORY);
define('AUTOLOAD_CACHE', TMP_DIR . DIRECTORY_SEPARATOR . PROJECT_NAME . '.atoum.cache');

if (false === is_dir(COVERAGE_DIRECTORY)) {
    mkdir(COVERAGE_DIRECTORY, 0777, true);
}

//if (false === is_dir(COVERAGE_DIRECTORY_OLD)) {
//    mkdir(COVERAGE_DIRECTORY_OLD, 0777, true);
//}

$testDirectories = array(
    'src',
);

$autoloader = atoum\autoloader::get();
$autoloader->setCacheFile(AUTOLOAD_CACHE);

//$coverageField = new coverage\html(COVERAGE_TITLE, COVERAGE_DIRECTORY_OLD);
//$coverageField->setRootUrl(COVERAGE_WEB_PATH);

foreach ($testDirectories as $d) {
    $runner->addTestsFromDirectory($d);
}

$cliReport = $script->addDefaultReport();
//$cliReport->addField($coverageField);

$coverage = new new_coverage\html();
$coverage->addWriter(new \mageekguy\atoum\writers\std\out());
$coverage->setOutPutDirectory(COVERAGE_DIRECTORY);
$runner->addReport($coverage);
