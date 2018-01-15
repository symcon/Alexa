<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/Validator.php';

class ValidationTest extends TestCaseSymconValidation
{
    public function testValidateLibrary(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }

    public function testValidateAssistant(): void
    {
        $this->validateModule(__DIR__ . '/../Alexa');
    }
}
