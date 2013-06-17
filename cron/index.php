<?php
// function exception_handler($exception) {
// error_log ("Uncaught exception: " . $exception->getMessage(), 3, 'PATH_TO_LOG_FILE');
// }
// set_exception_handler('exception_handler');

use Destiny\Application;
use Destiny\AppException;
use Destiny\Session;
use Destiny\Scheduler;
use Destiny\Config;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\MemoryPeakUsageProcessor;

$base = realpath ( __DIR__ . '/../' );
$loader = require $base . '/vendor/autoload.php';
$loader->add ( 'Destiny', $base . '/lib/' );
Config::load ( $base . '/config/config.php', parse_ini_file ( $base . '/lib/.version' ) );

$log = new Logger ( 'cron' );
$log->pushHandler ( new StreamHandler ( Config::$a ['log'] ['path'] . 'cron.log', Logger::DEBUG ) );
$log->pushProcessor ( new WebProcessor () );
$log->pushProcessor ( new ProcessIdProcessor () );
$log->pushProcessor ( new MemoryPeakUsageProcessor () );

$dbConfig = new \Doctrine\DBAL\Configuration ();
$db = \Doctrine\DBAL\DriverManager::getConnection ( Config::$a ['db'], $dbConfig );

// $cache = new \Doctrine\Common\Cache\FilesystemCache ( Config::$a ['cache'] ['path'] );
$cache = new \Doctrine\Common\Cache\ZendDataCache ();

$app = Application::instance ();
$app->setLogger ( $log );
$app->setConnection ( $db );
$app->setCacheDriver ( $cache );

// Cron is run every 60 seconds.
// There can be a time where actions are executed before they have ended
$scheduler = new Scheduler ( Config::$a ['scheduler'] );
$scheduler->setLogger ( $log );
$scheduler->loadSchedule ();
$stime = microtime ( true );

try {
	$scheduler->executeShedule ();
	echo PHP_EOL . 'Scheduler completed';
} catch ( AppException $e ) {
	$log->error ( $e->getMessage () );
	echo PHP_EOL . 'Scheduler completed with errors';
} catch ( \Exception $e ) {
	$log->critical ( $e->getMessage () );
	echo PHP_EOL . 'Scheduler completed with errors';
}
echo PHP_EOL . 'Completed in ' . (microtime ( true ) - $stime) . ' seconds';
echo PHP_EOL;
?>