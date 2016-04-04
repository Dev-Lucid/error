<?php
namespace Lucid\Component\Error;
use \Lucid\Lucid;

class Error implements ErrorInterface
{
    protected $defaultMessage = 'An error has occured.';
    protected $debugStages    = [];
    protected $logger = null;

    public function __construct($logger=null)
    {
        if (is_null($logger)) {
            $this->logger = new \Lucid\Component\BasicLogger\BasicLogger();
        } else {
            if (is_object($logger) === false || in_array('Psr\\Log\\LoggerInterface', class_implements($logger)) === false) {
                throw new \Exception('Error contructor parameter $logger must either be null, or implement Psr\\Log\\LoggerInterface. If null is passed, then an instance of Lucid\\Component\\BasicLogger\\BasicLogger will be instantiated instead, and all messages will be passed along to error_log();');
            }
            $this->logger = $logger;
        }
    }

    public function setReportingDirective($value)
    {
        error_reporting($value);
    }

    public function setDefaultMessage(string $mesage)
    {
        $this->defaultMessage = $mesage;
    }

    public function setDebugStages(...$stages)
    {
        $this->debugStages = $stages;
    }

    public function isDebugStage(): bool
    {
        return in_array(lucid::$stage, $this->debugStages);
    }

    public function registerHandlers()
    {
        register_shutdown_function([$this, 'shutdown']);
        set_error_handler(function(int $errno, string $errstr, string $errfile='', int $errline=-1){
            call_user_func([$this, 'shutdown'], [[
                'type'    => $errno,
                'file'    => $errfile,
                'line'    => $errline,
                'message' => $errstr,
            ]]);
        });
        set_exception_handler([$this, 'shutdown']);
    }

    protected function buildErrorString($e): string
    {
        # transform the exception object into an array if necessary. This lets this code be called from
        # either a catch or a shutdown function
        $error = null;
        if (is_array($e) === false) {
            $arrayError = [];
            $arrayError['type']    = $e->getCode();
            $arrayError['file']    = $e->getFile();
            $arrayError['line']    = $e->getLine();
            $arrayError['message'] = $e->getMessage();
            $arrayError['trace']   = $e->getTraceAsString();
            $error = $arrayError;
        } elseif (isset($e[0])) {
            $error = array_shift($e);
        } else {
            $error = $e;
        }
        return str_replace(lucid::$path, '', $error['file']).'#'.$error['line'].': '.$error['message'];
    }


    public function shutdown($error=null)
    {
        $error = $error ?? error_get_last();
        if (is_null($error) === false) {
            lucid::error()->handle($error);
        }
    }

    public function handle($e, bool $sendMessage=true)
    {
        lucid::response()->reset();

        $errorMessage = $this->buildErrorString($e);
        $backtraces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 9);
        $this->logger->error($errorMessage);
        foreach ($backtraces as $backtrace) {
            $msg = '    ';
            $msg .= (isset($backtrace['file']) === true)? str_replace(lucid::$path, '', $backtrace['file']) : '<unknown file>';
            $msg .= '#';
            $msg .= $backtrace['line'] ?? '?';
            $msg .= ' ';
            $msg .= $backtrace['class'] ?? '';
            $msg .= $backtrace['type'] ?? '';
            $msg .= $backtrace['function'] ?? '';
            lucid::logger()->error($msg);
            #lucid::logger()->error(print_r($backtrace, true));
        }




        # Only send the real error message on stages that are explicitly marked as debug stages
        if ($this->isDebugStage() === true) {
            lucid::response()->message($errorMessage);
        } else {
            lucid::response()->message($this->defaultMessage);
        }

        if ($sendMessage === true) {
            lucid::response()->write('error');
        }
    }
}
