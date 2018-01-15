<?php

declare(strict_types=1);

if (defined('PHPUNIT_TESTSUITE')) {
    trait Simulate
    {
        public function SimulateData(array $data): array
        {
            return $this->ProcessData($data);
        }
    }
} else {
    trait Simulate
    {
    }
}
