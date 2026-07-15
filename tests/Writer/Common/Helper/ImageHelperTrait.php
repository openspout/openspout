<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common\Helper;

trait ImageHelperTrait
{
    protected string $testImagePath;

    protected function setUp(): void
    {
        // Minimal 1×1 white PNG
        $this->testImagePath = sys_get_temp_dir().\DIRECTORY_SEPARATOR.'openspout_test_image.png';
        file_put_contents($this->testImagePath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwADhQGAWjR9awAAAABJRU5ErkJggg==', true));
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testImagePath)) {
            unlink($this->testImagePath);
        }
    }
}
