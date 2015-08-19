<?php
namespace Spider\Test\Stubs;
use Spider\Base\ThrowsNotSupportedTrait;

/**
 * Class ThrowsNotSupportedStub
 * @package Spider\Test\Stubs
 */
class ThrowsNotSupportedStub {
    use ThrowsNotSupportedTrait;

    public function __construct($config = null)
    {
        if (!is_null($config)) {
            $this->config = $config;
        }
    }

    public function thisIsNotSupported()
    {
        $this->notSupported("My test message");
        return true;
    }
}
