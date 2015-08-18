<?php
namespace Spider\Test\Stubs;

use Michaels\Manager\Contracts\IocContainerInterface;
use Michaels\Manager\Traits\ManagesIocTrait;
use Michaels\Manager\Traits\ManagesItemsTrait;

class IocContainerStub implements IocContainerInterface
{
    use ManagesItemsTrait, ManagesIocTrait;
}
