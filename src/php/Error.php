<?php
namespace Lucid\Component\Error;
use \Lucid\Lucid;

class Error implements ErrorInterface
{
    protected $defaultMessage = 'An error has occured.';
    protected $debugStages    = [];

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
            $error = $arrayError;
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

        $errorMessage = lucid::error()->buildErrorString($e);
        lucid::logger()->error($errorMessage);

        # Only send the real error message on stages that are explicitly marked as debug stages
        if ($this->isDebugStage() === true) {
            lucid::response()->error($errorMessage);
        } else {
            lucid::response()->error($this->defaultMessage);
        }

        if ($sendMessage === true) {
            lucid::response()->write('error');
        }
    }


    /*
    public function throwError($message)
    {
        $backtrace = debug_backtrace()[0];
        lucid::logger()->error(str_replace(lucid::$path, '', $backtrace['file']).'#'.$backtrace['line'].': '.$message);
        throw new \Exception();
    }
    */

    /*
    public function notFound($data, string $replaceSelector = '#body')
    {
        if ($data === false) {
            lucid::mvc()->view('error_data_not_found', ['replaceSelector'=>$replaceSelector]);
            #throw new Exception\Silent('Data not found');
        }
    }


    public function permissionDenied(string $replace_selector = '#body')
    {
        lucid::mvc()->view('error_permission_denied', ['replace_selector'=>$replace_selector]);
        #throw new Exception\Silent('Permission denied');
    }

    public function loginRequired(string $replace_selector = '#body')
    {
        lucid::mvc()->view('error_login_required', ['replace_selector'=>$replace_selector]);
        #throw new Exception\Silent('Login required');
    }
    */
}