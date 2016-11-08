<?php
namespace Api\Test;

use LogicException;
use Weew\HttpServer\HttpServer;

trait PHPServerTrait
{
    /**
     * @var HttpServer
     */
    private static $server;

    /**
     * @var int
     */
    protected static $port = 8888;

    /**
     * @var float
     */
    protected static $wait = 1.0;

    /**
     * @var string
     */
    protected static $host = 'localhost';

    /**
     * @var string
     */
    protected static $log_path = '/tmp/php-server.log';

    /**
     * start the php dev server
     * @param string $log
     */
    public static function startServer(string $log = '/dev/null')
    {
        if (!self::$server) {
            self::$server = new HttpServer(
                self::$host,
                self::$port,
                'app.php',
                self::$wait
            );
        }
        if (self::$server->isRunning()) {
            throw new LogicException('Server is already running');
        }
        self::$server->setLogFile($log);
        $dir = getcwd();
        chdir(__DIR__ . '/../../public/');
        self::$server->start();
        sleep(1); // Warming up for too long. See Issue #66
        chdir($dir);
    }

    /**
     * stop the php dev server
     */
    public static function stopServer()
    {
        if (self::$server && self::$server->isRunning()) {
            self::$server->stop();
        } else {
            throw new LogicException('Server is not running');
        }
    }

    /**
     * Get log file content
     * @param $file_name
     * @param int $num_line
     * @return string
     */
    public static function getFileContent($file_name, int $num_line = 15)
    {
        $file = escapeshellarg($file_name);
        $cmd = "sudo tail -n $num_line $file";
        exec($cmd, $output);
        return implode("\n", $output);
    }

    /**
     * Get server log path
     * @return type
     */
    public static function getLogPath(): string
    {
        return self::$log_path;
    }
}
