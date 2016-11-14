<?php
namespace Taxusorg\Attachment\Facades;

use Illuminate\Support\Facades\Facade;
class Attachment extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'attachment';
    }
}