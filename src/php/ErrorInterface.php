<?php
namespace Lucid\Component\Error;

interface ErrorInterface
{
    public function __construct($logger=null);
    public function setReportingDirective($value);
    public function setDefaultMessage(string $mesage);
    public function setDebugStages(...$stages);
    public function isDebugStage();
    public function registerHandlers();
}