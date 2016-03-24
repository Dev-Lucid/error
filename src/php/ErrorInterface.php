<?php
namespace Lucid\Component\Error;

interface ErrorInterface
{
    public function setReportingDirective($value);
    public function setDefaultMessage(string $mesage);
    public function setDebugStages(...$stages);
    public function isDebugStage();
    public function registerHandlers();
    /*
    public function shutdown($error);
    public function handle($e, bool $send_message);
    public function notFound($data, string $replace_selector);
    public function permissionDenied(string $replace_selector);
    public function loginRequired(string $replace_selector);
    */
}